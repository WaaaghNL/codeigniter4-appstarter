<!DOCTYPE html>
<html>
<head>
    <title>Gebruiker aanmaken</title>
</head>
<body>

<h1>Gebruiker aanmaken</h1>

<form action="/shield/store" method="post">
    <?= csrf_field() ?>

    <p>
        <label>Gebruikersnaam</label><br>
        <input type="text" name="username">
    </p>

    <p>
        <label>Email</label><br>
        <input type="email" name="email">
    </p>

    <p>
        <label>Wachtwoord</label><br>
        <input type="password" name="password">
    </p>

    <button type="submit">
        Opslaan
    </button>

</form>

<p>
    <a href="<?= site_url('shield'); ?>">Terug</a>
</p>

</body>
</html>