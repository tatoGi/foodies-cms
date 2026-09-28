<!DOCTYPE html>
<html lang="{{ $isEnglish ? 'en' : 'ka' }}">
<body style="margin:0;padding:24px;background:#f5efe6;font-family:Arial,sans-serif;color:#3d2b1f">
    <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:16px;padding:32px;text-align:center">
        <h1 style="margin:0 0 8px;font-size:22px">BiteClub</h1>
        <p style="margin:0 0 24px;font-size:15px">
            @if ($isReset)
                {{ $isEnglish ? 'Use this code to reset your password:' : 'პაროლის აღსადგენად გამოიყენეთ ეს კოდი:' }}
            @else
                {{ $isEnglish ? 'Use this code to confirm your e-mail:' : 'ელფოსტის დასადასტურებლად გამოიყენეთ ეს კოდი:' }}
            @endif
        </p>
        <p style="margin:0 0 24px;font-size:36px;font-weight:bold;letter-spacing:8px">{{ $code }}</p>
        <p style="margin:0;font-size:13px;color:#6b5b4d">
            {{ $isEnglish ? 'The code is valid for 15 minutes. If you did not request it, ignore this e-mail.' : 'კოდი მოქმედებს 15 წუთი. თუ ეს თქვენ არ მოგითხოვიათ, უგულებელყავით წერილი.' }}
        </p>
    </div>
</body>
</html>
