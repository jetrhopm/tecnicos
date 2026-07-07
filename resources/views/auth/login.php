<div class="text-center mb-4">
    <div class="brand-mark mx-auto mb-3"></div>
    <h1 class="h4 mb-1" data-icon="&#128295;">Sistema Web de Gestion de Servicios Tecnicos</h1>
    <p class="text-muted mb-0">Acceso administrativo</p>
</div>
<form method="post" action="<?= e(url('/login')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="email" data-icon="&#9993;">Email</label>
        <input class="form-control" id="email" type="email" name="email" value="admin@local.test" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label" for="password" data-icon="&#128274;">Contrasena</label>
        <input class="form-control" id="password" type="password" name="password" value="password" required>
    </div>
    <button class="btn btn-primary w-100" data-icon="&#128274;">Entrar</button>
</form>
