    </main>

    <footer class="footer">
        <div class="container footer-content">
            <div class="footer-info">
                <div class="footer-logo">
                    <span class="logo-icon">✂️</span>
                    <span class="logo-text">Reserva<span class="logo-highlight">Local</span></span>
                </div>
                <p class="footer-tagline">Plataforma web para descubrir y reservar citas en barberías y salones de belleza locales con disponibilidad en tiempo real.</p>
            </div>
            <div class="footer-links">
                <h4>Enlaces Rápidos</h4>
                <ul>
                    <li><a href="<?= url('/explorar.php') ?>">Explorar Barberías y Salones</a></li>
                    <li><a href="<?= url('/login.php') ?>">Acceso a Clientes y Profesionales</a></li>
                    <li><a href="<?= url('/registro.php') ?>">Crear Cuenta Nueva</a></li>
                </ul>
            </div>
            <div class="footer-roles">
                <h4>Acceso a Paneles</h4>
                <ul>
                    <li><a href="<?= url('/propietario/') ?>">Portal de Propietarios</a></li>
                    <li><a href="<?= url('/profesional/') ?>">Portal de Profesionales</a></li>
                    <li><a href="<?= url('/administrador/') ?>">Administración General</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?= date('Y') ?> Reserva Local. Desarrollado con PHP nativo y MySQL.</p>
            </div>
        </div>
    </footer>

    <script src="<?= asset('assets/js/app.js') ?>"></script>
    <?php if (isset($extraScripts) && is_array($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?= asset($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
