<?php
$img = URLROOT . '/img/landing_bg.png';
require_once APPROOT . '/views/layouts/header.php';
?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/presentacion.css?v=<?php echo time(); ?>">

<div class="pres-deck" id="presDeck">
    <div class="pres-progress" id="presProgress" style="width:0;"></div>

    <header class="pres-topbar">
        <div class="pres-topbar-brand">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="stroke:#818cf8;-webkit-text-fill-color:initial;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            ConectaBarrio
        </div>
        <div class="pres-topbar-actions">
            <a href="<?php echo URLROOT; ?>/" class="btn btn-secondary">Inicio</a>
            <button type="button" class="btn btn-secondary" id="presFullscreen" title="Pantalla completa">⛶</button>
            <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-primary">Ingresar al Portal</a>
        </div>
    </header>

    <div class="pres-viewport">
        <div class="pres-track">

            <!-- 1. Portada -->
            <section class="pres-slide pres-slide--cover" aria-hidden="false">
                <div class="pres-slide-bg" style="background-image:url('<?php echo $img; ?>');"></div>
                <div class="pres-slide-inner pres-cover">
                    <span class="pres-eyebrow">Presentación ConectaBarrio · Chile 2026</span>
                    <h1 class="pres-title">Tu organización<br><span>más conectada y bajo control</span></h1>
                    <p class="pres-subtitle">ConectaBarrio digitaliza el padrón de socios, la tesorería, las asambleas y la comunicación vecinal en una sola plataforma — pensada para juntas de vecinos, clubes deportivos y organizaciones territoriales.</p>
                </div>
            </section>

            <!-- 2. El desafío -->
            <section class="pres-slide">
                <div class="pres-slide-bg" style="background-image:url('https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1400&q=80');"></div>
                <div class="pres-slide-inner">
                    <span class="pres-slide-num">Lámina 02</span>
                    <h2 class="pres-h2">El desafío de las organizaciones sin control digital</h2>
                    <p class="pres-lead">Miles de directivas en Chile aún gestionan con cuadernos, planillas sueltas y grupos de WhatsApp. Eso genera errores, desconfianza y sobrecarga para quienes asumen voluntariamente.</p>
                    <div class="pres-problems">
                        <div class="pres-problem-card">
                            <strong>Padrón desactualizado</strong>
                            <p>Socios en papel, domicilios sin georreferencia y cuotas que nadie sabe con certeza quién pagó.</p>
                        </div>
                        <div class="pres-problem-card">
                            <strong>Tesorería opaca</strong>
                            <p>Ingresos y egresos repartidos en recibos. Los socios piden transparencia y la directiva no tiene tiempo de armar informes.</p>
                        </div>
                        <div class="pres-problem-card">
                            <strong>Asambleas sin respaldo</strong>
                            <p>Listas de asistencia a mano, quórum dudoso y minutas que tardan semanas en quedar archivadas.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. La solución -->
            <section class="pres-slide">
                <div class="pres-slide-bg" style="background-image:url('<?php echo $img; ?>');"></div>
                <div class="pres-slide-inner pres-split">
                    <div class="pres-split-content">
                        <span class="pres-slide-num">Lámina 03</span>
                        <h2 class="pres-h2">Una plataforma integral, simple y segura</h2>
                        <p class="pres-lead">ConectaBarrio centraliza lo que hoy está fragmentado: socios, finanzas, reuniones, documentos y comunicación — con roles diferenciados para directiva, tesoreros, secretarios y socios.</p>
                        <ul class="pres-bullets">
                            <li>Portal web accesible desde celular o computador, sin instalar apps.</li>
                            <li>Datos por organización, con permisos delegables a socios de confianza.</li>
                            <li>Diseñado para el contexto chileno: RUT, personalidad jurídica y cuotas vecinales.</li>
                            <li>Transparencia que fortalece la confianza y reduce conflictos internos.</li>
                        </ul>
                    </div>
                    <div class="pres-image-frame">
                        <img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=900&q=80" alt="Comunidad organizada trabajando en conjunto" loading="lazy">
                        <span class="pres-image-caption">Fortalecer el vínculo entre directiva y socios</span>
                    </div>
                </div>
            </section>

            <!-- 4. Padrón y socios -->
            <section class="pres-slide">
                <div class="pres-slide-bg" style="background-image:url('https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1400&q=80');"></div>
                <div class="pres-slide-inner pres-split">
                    <div class="pres-image-frame">
                        <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=900&q=80" alt="Vecindario y territorio comunitario" loading="lazy">
                        <span class="pres-image-caption">Padrón digital con calles jurisdiccionales</span>
                    </div>
                    <div class="pres-split-content">
                        <span class="pres-slide-num">Lámina 04 · Comunidad</span>
                        <h2 class="pres-h2">Padrón de socios bajo control</h2>
                        <p class="pres-lead">Deje atrás las planillas Excel que nadie actualiza. Digitalice el padrón completo de su organización.</p>
                        <ul class="pres-bullets">
                            <li>Alta, edición e invitación de socios con registro en línea.</li>
                            <li>Calles y sectores jurisdiccionales configurables.</li>
                            <li>Definición de valor y vigencia de cuotas por período.</li>
                            <li>Mapa comunitario con geolocalización de domicilios.</li>
                            <li>Socios pueden solicitar actualización de sus propios datos.</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 5. Finanzas -->
            <section class="pres-slide">
                <div class="pres-slide-bg" style="background-image:url('https://images.unsplash.com/photo-1554224311-beee415c201f?w=1400&q=80');"></div>
                <div class="pres-slide-inner pres-split">
                    <div class="pres-split-content">
                        <span class="pres-slide-num">Lámina 05 · Finanzas</span>
                        <h2 class="pres-h2">Tesorería transparente y ordenada</h2>
                        <p class="pres-lead">La desconfianza financiera es la principal causa de crisis en organizaciones vecinales. ConectaBarrio pone números claros al alcance de todos.</p>
                        <ul class="pres-bullets">
                            <li>Registro de ingresos y egresos con conceptos de caja personalizables.</li>
                            <li>Flujo de caja anual con visualizaciones gráficas.</li>
                            <li>Cierres mensuales para dejar constancia contable.</li>
                            <li>Recordatorios automáticos por correo a socios con cuotas pendientes.</li>
                            <li>Comprobantes disponibles para cada socio en su portal.</li>
                        </ul>
                    </div>
                    <div class="pres-image-frame">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=900&q=80" alt="Panel de control financiero" loading="lazy">
                        <span class="pres-image-caption">Balances e ingresos visibles para directiva y socios</span>
                    </div>
                </div>
            </section>

            <!-- 6. Reuniones y QR -->
            <section class="pres-slide">
                <div class="pres-slide-bg" style="background-image:url('https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?w=1400&q=80');"></div>
                <div class="pres-slide-inner pres-split">
                    <div class="pres-image-frame">
                        <img src="https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=900&q=80" alt="Registro de asistencia con celular" loading="lazy">
                        <span class="pres-image-caption">Asistencia vía QR en asambleas y reuniones</span>
                    </div>
                    <div class="pres-split-content">
                        <span class="pres-slide-num">Lámina 06 · Operaciones</span>
                        <h2 class="pres-h2">Asambleas con quórum respaldado</h2>
                        <p class="pres-lead">Garantice integridad en ordinarias y extraordinarias con un registro digital confiable.</p>
                        <ul class="pres-bullets">
                            <li>Creación y gestión de reuniones con convocatoria.</li>
                            <li>Registro de asistencia manual o escaneo QR desde el celular de la directiva.</li>
                            <li>Cada socio muestra su código QR personal en el dashboard.</li>
                            <li>Minutas de reunión archivadas y consultables.</li>
                            <li>Historial de asistencia por socio para decisiones informadas.</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- 7. Comunicación y calendario -->
            <section class="pres-slide">
                <div class="pres-slide-bg" style="background-image:url('https://images.unsplash.com/photo-1506784365847-bbad939e9335?w=1400&q=80');"></div>
                <div class="pres-slide-inner">
                    <span class="pres-slide-num">Lámina 07 · Comunicación</span>
                    <h2 class="pres-h2">Mantenga a la comunidad informada y participativa</h2>
                    <p class="pres-lead">Menos mensajes perdidos en WhatsApp. Más avisos oportunos y actividades visibles para todos.</p>
                    <div class="pres-features">
                        <div class="pres-feature">
                            <div class="pres-feature-icon">
                                <svg viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </div>
                            <h3>Calendario de actividades</h3>
                            <p>Asambleas, mingas y eventos en un mural comunitario con vista mensual en el dashboard.</p>
                        </div>
                        <div class="pres-feature">
                            <div class="pres-feature-icon">
                                <svg viewBox="0 0 24 24" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M22 6l-10 7L2 6"/></svg>
                            </div>
                            <h3>Recordatorios por correo</h3>
                            <p>Notificaciones masivas para actividades próximas y avisos de cuotas por regularizar.</p>
                        </div>
                        <div class="pres-feature">
                            <div class="pres-feature-icon">
                                <svg viewBox="0 0 24 24" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </div>
                            <h3>Portal de actividades</h3>
                            <p>Talleres, beneficios sociales y anuncios de interés vecinal en un solo lugar.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 8. Documentación -->
            <section class="pres-slide">
                <div class="pres-slide-bg" style="background-image:url('https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1400&q=80');"></div>
                <div class="pres-slide-inner pres-split">
                    <div class="pres-split-content">
                        <span class="pres-slide-num">Lámina 08 · Institucional</span>
                        <h2 class="pres-h2">Documentación legal e institucional</h2>
                        <p class="pres-lead">Respalde la seriedad de su organización ante socios, municipalidades y terceros.</p>
                        <ul class="pres-bullets">
                            <li>Registro de N° de personalidad jurídica por organización.</li>
                            <li>Repositorio de documentación legal para la directiva.</li>
                            <li>Gestión de documentos comunitarios compartidos.</li>
                            <li>Trazabilidad: solo quien sube un archivo puede eliminarlo.</li>
                            <li>Todo accesible desde el portal, sin carpetas perdidas en el correo.</li>
                        </ul>
                    </div>
                    <div class="pres-image-frame">
                        <img src="https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=900&q=80" alt="Documentos y archivo institucional" loading="lazy">
                        <span class="pres-image-caption">Estatutos, actas y resoluciones en un solo lugar</span>
                    </div>
                </div>
            </section>

            <!-- 9. Portal del socio -->
            <section class="pres-slide">
                <div class="pres-slide-bg" style="background-image:url('https://images.unsplash.com/photo-1524661135-423995f22d0b?w=1400&q=80');"></div>
                <div class="pres-slide-inner">
                    <span class="pres-slide-num">Lámina 09 · Empoderamiento</span>
                    <h2 class="pres-h2">Cada socio con su propio portal</h2>
                    <p class="pres-lead">La digitalización no es solo para la directiva: los socios ven su estado, participan y confían.</p>
                    <div class="pres-features">
                        <div class="pres-feature">
                            <div class="pres-feature-icon">
                                <svg viewBox="0 0 24 24" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </div>
                            <h3>Mi perfil y datos</h3>
                            <p>Consulta de información personal y solicitud de cambios ante la directiva.</p>
                        </div>
                        <div class="pres-feature">
                            <div class="pres-feature-icon">
                                <svg viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                            </div>
                            <h3>Estado financiero</h3>
                            <p>Visualización de cuotas, comprobantes y flujo de caja de la organización.</p>
                        </div>
                        <div class="pres-feature">
                            <div class="pres-feature-icon">
                                <svg viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6v6H9z"/></svg>
                            </div>
                            <h3>QR de asistencia</h3>
                            <p>Código personal para marcar presencia en reuniones con un solo escaneo.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 10. Impacto -->
            <section class="pres-slide">
                <div class="pres-slide-bg" style="background-image:url('<?php echo $img; ?>');"></div>
                <div class="pres-slide-inner">
                    <span class="pres-slide-num">Lámina 10 · Impacto</span>
                    <h2 class="pres-h2">Resultados que transforman la gestión diaria</h2>
                    <p class="pres-lead">Organizaciones que ordenan su información reducen conflictos, ahorran horas de trabajo voluntario y proyectan mayor seriedad institucional.</p>
                    <div class="pres-stats">
                        <div class="pres-stat">
                            <span class="pres-stat-value">−70%</span>
                            <span class="pres-stat-label">Menos tiempo en planillas y cobranza manual</span>
                        </div>
                        <div class="pres-stat">
                            <span class="pres-stat-value">100%</span>
                            <span class="pres-stat-label">Trazabilidad de asistencia y actas de reunión</span>
                        </div>
                        <div class="pres-stat">
                            <span class="pres-stat-value">24/7</span>
                            <span class="pres-stat-label">Acceso al portal para socios y directiva</span>
                        </div>
                        <div class="pres-stat">
                            <span class="pres-stat-value">1</span>
                            <span class="pres-stat-label">Plataforma unificada: socios, finanzas y operaciones</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 11. Cierre -->
            <section class="pres-slide pres-slide--cover">
                <div class="pres-slide-bg" style="background-image:url('<?php echo $img; ?>');"></div>
                <div class="pres-slide-inner pres-cta">
                    <span class="pres-eyebrow">Próximo paso</span>
                    <h2 class="pres-title">Modernice su organización<br><span>sin complicaciones</span></h2>
                    <p class="pres-subtitle">ConectaBarrio está listo para acompañar a juntas de vecinos, clubes y organizaciones territoriales que buscan profesionalizar su gestión con herramientas pensadas para Chile.</p>
                    <div class="pres-cta-buttons">
                        <a href="<?php echo URLROOT; ?>/auth/login" class="btn btn-primary" style="padding:0.85rem 1.75rem;">Ingresar al Portal</a>
                        <a href="https://wa.me/56950001071" target="_blank" rel="noopener" class="btn btn-secondary" style="padding:0.85rem 1.75rem;">Contactar por WhatsApp</a>
                    </div>
                    <p class="pres-contact">
                        WhatsApp <a href="https://wa.me/56950001071" target="_blank" rel="noopener">+56 9 5000 1071</a>
                        · Correo <a href="mailto:contacto@conectatubarrio.cl">contacto@conectatubarrio.cl</a>
                    </p>
                </div>
            </section>

        </div>
    </div>

    <p class="pres-hint">← → flechas · Espacio para avanzar · Deslizar en móvil</p>

    <nav class="pres-nav" aria-label="Navegación de presentación">
        <button type="button" class="pres-nav-btn" id="presPrev" aria-label="Lámina anterior" disabled>
            <svg viewBox="0 0 24 24" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <div class="pres-dots" id="presDots"></div>
        <span class="pres-counter" id="presCounter">1 / 11</span>
        <button type="button" class="pres-nav-btn" id="presNext" aria-label="Lámina siguiente">
            <svg viewBox="0 0 24 24" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </nav>
</div>

<script src="<?php echo URLROOT; ?>/js/presentacion.js?v=<?php echo time(); ?>"></script>
<?php require_once APPROOT . '/views/layouts/footer.php'; ?>
