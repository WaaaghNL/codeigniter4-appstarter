<!DOCTYPE html>
<html>
<head>
    <title>User management</title>
</head>
<body>

<h1>User management</h1>

<p>
    <a href="<?= site_url('shield/create');?>">create user</a>
</p>

<table border="1" cellpadding="5">
    <thead>
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Groups</th>
        <th>Actions</th>
    </tr>
    </thead>

    <tbody>

    <?php foreach ($users as $user): ?>

        <tr>
            <td><?= esc($user->id) ?></td>
            <td><?= esc($user->username) ?></td>
            <td><?= esc($user->email) ?></td>

            <td>
                <?= implode(', ', $user->getGroups()) ?>
            </td>

            <td>
                <a href="<?=site_url("shield/edit");?>/<?= esc($user->id) ?>">Edit</a> | <a href="<?=site_url("shield/permissions");?>/<?= esc($user->id) ?>">Permissions</a> | <a href="<?=site_url("shield/delete");?>/<?= esc($user->id) ?>" onclick="return confirm('Remove User?')">Remove</a>
            </td>
        </tr>

    <?php endforeach ?>

    </tbody>
</table>

</body>
</html>