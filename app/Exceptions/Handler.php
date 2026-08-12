<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Capturar errores cuando se llama a métodos en null (sesión expirada)
        $this->renderable(function (\Error $e, $request) {
            // Detectar llamadas a miembros en null (típico de sesión expirada)
            if ($e instanceof \Error && 
                (str_contains($e->getMessage(), 'Call to a member function') && 
                 str_contains($e->getMessage(), 'on null'))) {
                
                // Si es una petición AJAX/JSON
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Sesión expirada. Por favor inicie sesión nuevamente.'
                    ], 401);
                }
                
                // Mostrar vista personalizada
                return response()->view('errors.session-expired', [], 401);
            }
        });
    }

    /**
     * Maneja los casos de usuario no autenticado (sesión expirada).
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sesión expirada. Por favor inicie sesión nuevamente.'
            ], 401);
        }

        // Mostrar vista personalizada en lugar de redirección
        return response()->view('errors.session-expired', [], 401);
    }

    /**
     * Sobrescribir render para capturar más casos
     */
    public function render($request, Throwable $exception)
    {
        // Capturar AuthenticationException directamente aquí también
        if ($exception instanceof AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }

        return parent::render($request, $exception);
    }
}