'use strict';

const AppCore = {
    modules: {},
    currentModule: null,

    registerModule(name, module) {
        this.modules[name] = module;
    },

    initModule(name) {
        if (this.currentModule && typeof this.currentModule.destroy === 'function') {
            this.currentModule.destroy();
        }

        const module = this.modules[name];
        console.log('AppCore.initModule llamado con:', name);
        if (module && typeof module.init === 'function') {
            console.log(`Initializing module: ${name}`);
            this.currentModule = module;
            this.currentModule.init();
        } else {
            console.log(`No module to initialize for: ${name}`);
            this.currentModule = null;
        }
    },

    destroyCurrentModule() {
        if (this.currentModule && typeof this.currentModule.destroy === 'function') {
            console.log('Destroying current module.');
            this.currentModule.destroy();
            this.currentModule = null;
        }
    }
};

window.AppCore = AppCore;

(function() {
    'use strict';
    
    const PanelModule = {
        currentView: null,
        menuDashboard: null,
        contenedor: null,
        
        init() {
            this.initializeElements();
            this.initializeMenuCollapse();
            this.initializeViewLoading();
            this.checkReloadParams();
            if (!this.currentView && !window.location.search.includes('reload=true')) {
                this.loadView('inicio/inicio', 'inicio/inicio.js');
            }
        },
        
        initializeElements() {
            this.menuDashboard = document.getElementById('menuDashboard');
            this.contenedor = document.getElementById('contenidoDinamico');
        },
        
        initializeMenuCollapse() {
            if (!this.menuDashboard) return;
            this.menuDashboard.addEventListener('mouseenter', () => {
                this.menuDashboard.classList.remove('collapsed');
            });
            this.menuDashboard.addEventListener('mouseleave', () => {
                this.menuDashboard.classList.add('collapsed');
            });
            if (window.innerWidth > 700) {
                this.menuDashboard.classList.add('collapsed');
            }
        },
        
        initializeViewLoading() {
            if (!this.contenedor) return;
            document.querySelectorAll('.btn-opcion[data-vista]').forEach(btn => {
                btn.addEventListener('click', () => this.loadView(btn.dataset.vista, btn.dataset.script));
            });
            document.querySelectorAll('.menu .enlace[data-vista]').forEach(enlace => {
                enlace.addEventListener('click', (e) => {
                    e.preventDefault();
                    const vista = enlace.getAttribute('data-vista');
                    const script = enlace.dataset.script;
                    if (vista) this.loadView(vista, script);
                });
            });
        },
        
        async verifyAccess(nombreVista) {
            try {
                const res = await fetch(`../utiles/verificar_acceso.php?action=access&vista=${encodeURIComponent(nombreVista)}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return { ok: false, status: res.status };
                const data = await res.json();
                return { ok: !!data.success, status: 200, data };
            } catch (e) {
                console.error('verifyAccess error:', e);
                return { ok: false, status: 0 };
            }
        },
        
        async loadView(nombreVista, scriptName) {
            if (!this.contenedor) return;
            this.currentView = nombreVista;
            this.updateActiveMenu(nombreVista);
            this.showLoading();
            
            // Destruir el módulo JS de la vista anterior
            AppCore.destroyCurrentModule();

            try {
                const permiso = await this.verifyAccess(nombreVista);
                if (!permiso.ok) {
                    const msg = permiso.status === 403
                        ? 'No tienes permisos para acceder a esta vista.'
                        : 'No se pudo verificar el acceso a la vista.';
                    this.showError(msg);
                    return;
                }

                const rutaVista = this.getViewPath(nombreVista);
                const response = await fetch(rutaVista, { 
                    credentials: 'same-origin',
                    headers: { 'Cache-Control': 'no-cache' }
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                
                const html = await response.text();
                this.contenedor.innerHTML = html;
                
                // Cargar y ejecutar el script del módulo nuevo
                this.loadModuleScript(scriptName);
                
            } catch (error) {
                console.error('Error loading view:', error);
                this.showError(`Error al cargar la vista "${nombreVista}". Intenta de nuevo.`);
            }
        },

        loadModuleScript(scriptName) {
            const oldScript = document.getElementById('dynamic-view-script');
            if (oldScript) oldScript.remove();

            const moduleContainer = this.contenedor.querySelector('[data-module]');
            const moduleName = moduleContainer ? moduleContainer.dataset.module : null;

            if (scriptName) {
                const newScript = document.createElement('script');
                newScript.id = 'dynamic-view-script';
                // Importante: como Panel.php está en /panel/, hay que salir al nivel raíz para acceder a doctores/, citas/, etc.
                newScript.src = `../${scriptName}?v=${Date.now()}`;
                newScript.onload = () => {
                    if (moduleName) {
                        AppCore.initModule(moduleName);
                    }
                };
                document.body.appendChild(newScript);
            } else if (moduleName) {
                // Si no hay script explícito pero la vista tiene un módulo declarado
                AppCore.initModule(moduleName);
            }
        },
        
        getViewPath(nombreVista) {
            // Como las vistas están en carpetas en la raíz, salimos de /panel/
            return `../${nombreVista}.php`;
        },
        
        updateActiveMenu(nombreVista) {
            document.querySelectorAll('.enlace').forEach(enlace => {
                enlace.classList.remove('active');
            });
            const enlaceActivo = document.querySelector(`[data-vista="${nombreVista}"]`);
            if (enlaceActivo) enlaceActivo.classList.add('active');
        },
        
        showLoading() {
            if (!this.contenedor) return;
            this.contenedor.innerHTML = `
                <div class="loading-container">
                    <p>Cargando...</p>
                </div>
            `;
        },
        
        showError(mensaje) {
            if (!this.contenedor) return;
            this.contenedor.innerHTML = `
                <div class="error-container">
                    <p>${mensaje}</p>
                </div>
            `;
        },
        
        reloadCurrentView() {
            if (this.currentView) {
                const activeLink = document.querySelector(`[data-vista="${this.currentView}"]`);
                const scriptName = activeLink ? activeLink.dataset.script : null;
                this.loadView(this.currentView, scriptName);
            }
        },
        
        checkReloadParams() {
            if (window.location.search.includes('reload=true')) {
                setTimeout(() => {
                    this.reloadCurrentView();
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 100);
            }
        },
        
        destroy() {
            this.currentView = null;
        }
    };
    
    document.addEventListener('DOMContentLoaded', () => {
        PanelModule.init();
    });
    
    window.panelUtils = {
        loadView: (vista, script) => PanelModule.loadView(vista, script),
        reloadCurrentView: () => PanelModule.reloadCurrentView()
    };
})();