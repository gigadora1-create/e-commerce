<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class HrEmployeeSyncService
{
    public function sync(): array
    {
        $url = config('services.hr_employees.url');
        $token = config('services.hr_employees.token');

        if (blank($url) || blank($token)) {
            throw new RuntimeException('La integración de Recursos Humanos no está configurada.');
        }

        $response = Http::acceptJson()
            ->withToken($token)
            ->timeout((int) config('services.hr_employees.timeout', 20))
            ->retry(2, 500)
            ->get($url);

        $response->throw();
        $employees = $response->json('data');

        if (!is_array($employees)) {
            throw new RuntimeException('La respuesta de Recursos Humanos no contiene la colección de empleados esperada.');
        }

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($employees as $employee) {
            $employeeId = trim((string) data_get($employee, 'id'));
            $name = trim((string) data_get($employee, 'names'));
            $email = $this->preferredEmail($employee);

            if ($employeeId === '' || $name === '' || $email === null) {
                $result['skipped']++;
                continue;
            }

            [$user, $matchedByName, $duplicate] = $this->findExistingUser($employeeId, $email, $name);

            if ($duplicate !== null) {
                $duplicate->update([
                    'hr_employee_id' => null,
                    'is_active' => false,
                ]);
            }

            $keepLocalEmail = $matchedByName
                || ($user !== null
                    && filled($user->hr_preferred_email)
                    && !hash_equals(Str::lower($user->hr_preferred_email), Str::lower($user->email)));

            $attributes = [
                'hr_employee_id' => $employeeId,
                'hr_preferred_email' => $email,
                'name' => $name,
                // A name-only match enriches legacy accounts without changing
                // the email currently used to sign in.
                'email' => $keepLocalEmail ? $user?->email : $email,
                'position' => $this->nullableValue(data_get($employee, 'position')),
                'process' => $this->nullableValue(data_get($employee, 'process')),
                'regional' => $this->nullableValue(data_get($employee, 'regional')),
                'synced_from_hr_at' => now(),
            ];

            if ($user === null) {
                User::create($attributes + [
                    'telephone' => '',
                    'address' => '',
                    // Legacy field kept for compatibility with existing installations.
                    'user_type' => 'Usuario',
                    'password' => Hash::make(Str::password(40)),
                    'is_active' => true,
                ]);
                $result['created']++;
                continue;
            }

            $user->update($attributes);
            $result['updated']++;
        }

        return $result;
    }

    private function preferredEmail(array $employee): ?string
    {
        $email = data_get($employee, 'corporate_email') ?: data_get($employee, 'personal_email');

        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return Str::lower(trim($email));
    }

    private function findExistingUser(string $employeeId, string $email, string $name): array
    {
        $userById = User::query()->where('hr_employee_id', $employeeId)->first();
        $userByEmail = User::query()->where('email', $email)->first();

        // Existing manual accounts may predate the external identifier and use
        // another email. Names are compared by their words, so a source such
        // as "Bautista Rico Victor Alexis" can enrich "Victor Alexis Bautista Rico".
        $matches = User::query()
            ->whereNull('hr_employee_id')
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('synced_from_hr_at');
            })
            ->get()
            ->filter(fn (User $candidate) => $this->nameSignature($candidate->name) === $this->nameSignature($name))
            ->take(2)
            ->values();

        if ($matches->count() === 1) {
            $legacyUser = $matches->first();
            $duplicate = collect([$userById, $userByEmail])
                ->filter(fn (?User $candidate) => $candidate !== null && $candidate->isNot($legacyUser))
                ->first();

            return [$legacyUser, true, $duplicate];
        }

        return [$userById ?? $userByEmail, false, null];
    }

    private function nameSignature(string $name): string
    {
        $words = preg_split(
            '/\s+/',
            preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($name))),
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        sort($words, SORT_STRING);

        return implode(' ', $words);
    }

    private function nullableValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
