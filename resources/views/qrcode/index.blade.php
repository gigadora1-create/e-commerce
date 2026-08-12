<!-- resources/views/qrcode/index.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <title>Generate QR Codes</title>
</head>
<body>
    <form action="{{ route('qrcode.generate') }}" method="POST">
        @csrf
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <br><br>
        <label for="text">Text:</label>
        <input type="text" id="text" name="text" required>
        <br><br>
        <button type="submit">Generate QR Codes</button>
    </form>
</body>
</html>
