<?php
/**
 * Sidebar común para paneles de administración interna
 */
$panel = $panelType ?? 'propietario'; // 'propietario', 'profesional', 'administrador'
$currentScript = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="panel-sidebar">
    <div class="sidebar-header">
        <span class="sidebar-role-badge">
            <?= match($panel) {
                'propietario' => '🏢 PROPIETARIO',
                'profesional' => '💈 PROFESIONAL',
                'administrador' => '⚙️ ADMINISTRADOR',
                default => 'PANEL'
            } ?>
        </span>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <?php if ($panel === 'propietario'): ?>
                <li>
                    <a href="<?= url('/propietario/') ?>" class="<?= ($currentScript === 'index.php' && $currentDir === 'propietario') ? 'active' : '' ?>">
                        <span class="icon">📊</span> Resumen General
                    </a>
                </li>
                <li>
                    <a href="<?= url('/propietario/calendario.php') ?>" class="<?= $currentScript === 'calendario.php' ? 'active' : '' ?>">
                        <span class="icon">📅</span> Calendario de Citas
                    </a>
                </li>
                <li>
                    <a href="<?= url('/propietario/servicios.php') ?>" class="<?= $currentScript === 'servicios.php' ? 'active' : '' ?>">
                        <span class="icon">✂️</span> Gestión de Servicios
                    </a>
                </li>
                <li>
                    <a href="<?= url('/propietario/profesionales.php') ?>" class="<?= $currentScript === 'profesionales.php' ? 'active' : '' ?>">
                        <span class="icon">👥</span> Personal y Especialistas
                    </a>
                </li>
                <li>
                    <a href="<?= url('/propietario/horarios.php') ?>" class="<?= $currentScript === 'horarios.php' ? 'active' : '' ?>">
                        <span class="icon">⏰</span> Horarios y Descansos
                    </a>
                </li>
                <li>
                    <a href="<?= url('/propietario/excepciones.php') ?>" class="<?= $currentScript === 'excepciones.php' ? 'active' : '' ?>">
                        <span class="icon">🚫</span> Cierres y Excepciones
                    </a>
                </li>
                <li>
                    <a href="<?= url('/propietario/reportes.php') ?>" class="<?= $currentScript === 'reportes.php' ? 'active' : '' ?>">
                        <span class="icon">📈</span> Reportes y Finanzas
                    </a>
                </li>
            <?php elseif ($panel === 'profesional'): ?>
                <li>
                    <a href="<?= url('/profesional/') ?>" class="<?= ($currentScript === 'index.php' && $currentDir === 'profesional') ? 'active' : '' ?>">
                        <span class="icon">📋</span> Mi Agenda de Citas
                    </a>
                </li>
            <?php elseif ($panel === 'administrador'): ?>
                <li>
                    <a href="<?= url('/administrador/') ?>" class="<?= ($currentScript === 'index.php' && $currentDir === 'administrador') ? 'active' : '' ?>">
                        <span class="icon">📊</span> Panel Global
                    </a>
                </li>
                <li>
                    <a href="<?= url('/administrador/usuarios.php') ?>" class="<?= $currentScript === 'usuarios.php' ? 'active' : '' ?>">
                        <span class="icon">👤</span> Gestión de Usuarios
                    </a>
                </li>
                <li>
                    <a href="<?= url('/administrador/negocios.php') ?>" class="<?= $currentScript === 'negocios.php' ? 'active' : '' ?>">
                        <span class="icon">🏢</span> Negocios y Estados
                    </a>
                </li>
                <li>
                    <a href="<?= url('/administrador/auditoria.php') ?>" class="<?= $currentScript === 'auditoria.php' ? 'active' : '' ?>">
                        <span class="icon">🛡️</span> Registro de Auditoría
                    </a>
                </li>
            <?php endif; ?>

            <li class="sidebar-divider"></li>
            <li>
                <a href="<?= url('/explorar.php') ?>" target="_blank">
                    <span class="icon">🌐</span> Ver Portal Público ↗
                </a>
            </li>
            <li>
                <a href="<?= url('/perfil.php') ?>">
                    <span class="icon">⚙️</span> Mi Perfil
                </a>
            </li>
            <li>
                <a href="<?= url('/logout.php') ?>" class="sidebar-logout">
                    <span class="icon">🚪</span> Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>
</aside>
