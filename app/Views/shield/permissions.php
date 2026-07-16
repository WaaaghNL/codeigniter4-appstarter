<!DOCTYPE html>
<html>
<head>
    <title>Rechten aanpassen</title>
</head>
<body>

<h1>Groepen aanpassen</h1>

<h3><?= esc($user->username) ?></h3>

<?php $groups = $user->getGroups(); ?>

<form method="post" action="/shield/permissions/<?= esc($user->id) ?>">
	<?php foreach ($availableGroups as $group): ?>

	<p>
		<input
			type="checkbox"
			name="groups[]"
			value="<?= esc($group) ?>"
			<?= in_array($group, $groups) ? 'checked' : '' ?>
		>

		<?= esc($group) ?>
	</p>

	<?php endforeach ?>

    <button type="submit">
        Opslaan
    </button>

</form>

<p>
    <a= site_url('shield') ?>
</p>

</body>
</html>