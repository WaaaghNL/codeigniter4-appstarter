<!DOCTYPE html>
<html>
<head>
    <title>Gebruiker wijzigen</title>
</head>
<body>

<h1>Gebruiker wijzigen</h1>

<form method="post" action="/shield/update/<?= esc($user->id) ?>">
	<p>
        <label>Gebruikersnaam</label><br>
        <input
            type="text"
            name="username"
            value="<?= esc($user->username) ?>">
    </p>

    <p>
        <label>Email</label><br>
        <input
            type="email"
            name="email"
            value="<?= esc($user->email) ?>">
    </p>

    <p>
        <label>Nieuw wachtwoord</label><br>
        <input
            type="password"
            name="password">
        <br>
        Leeg laten om niet te wijzigen.
    </p>

    <button type="submit">
        Opslaan
    </button>

</form>

<p>
    <a href="<?= site_url('shield') ?>">Terug</a>
</p>