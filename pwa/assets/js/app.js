/**
 * DoliAgroPass PWA - Logic Core
 * Version: 4.1.0 (Added User Role Display)
 */

const { createApp, ref, computed, onMounted, watch } = Vue;

// --- ASSETS: Immagini SVG (Visual Soil Assessment - Manuale Pag. 24-34) ---
const IMG_VSA = {
    // A1 - Radici
    ROOTS_POOR: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGMzNTQ1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGR5PSIuM2VtIiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSIxNiIgdGV4dC1hbmNob3I9Im1pZGRsZSI+UkFESUNJIEEgIkwiPGJyPlNjaGlhY2NpYXRlPC90ZXh0Pjwvc3ZnPg==',
    ROOTS_MOD: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZjMTA3Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGR5PSIuM2VtIiBmaWxsPSIzMzMiIGZvbnQtc2l6ZT0iMTYiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkxJTUlUQVRFPGJyPihPcml6em9udGFsaSk8L3RleHQ+PC9zdmc+',
    ROOTS_GOOD: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMTk4NzU0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGR5PSIuM2VtIiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSIxNiIgdGV4dC1hbmNob3I9Im1pZGRsZSI+T1RUSU1FPGJyPihQcm9mb25kZSk8L3RleHQ+PC9zdmc+',
    
    // A2 - Suola
    PAN_POOR: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGMzNTQ1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGR5PSIuM2VtIiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSIxNiIgdGV4dC1hbmNob3I9Im1pZGRsZSI+U1VPTEEgTVSSVUE8YnI+KE1hc3NpdmEpPC90ZXh0Pjwvc3ZnPg==',
    PAN_MOD: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZjMTA3Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGR5PSIuM2VtIiBmaWxsPSIzMzMiIGZvbnQtc2l6ZT0iMTYiIHRleHQtYW5jaG9yPSJtaWRkbGUiPlBSRVNFTlRFPGJyPihDb24gY3JlcGUpPC90ZXh0Pjwvc3ZnPg==',
    PAN_GOOD: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMTk4NzU0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGR5PSIuM2VtIiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSIxNiIgdGV4dC1hbmNob3I9Im1pZGRsZSI+QVNTRU5URTxicj4oRnJpYWJpbGUpPC90ZXh0Pjwvc3ZnPg==',

    // A3 - Aggregati
    AGG_POOR: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGMzNTQ1Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGR5PSIuM2VtIiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSIxNiIgdGV4dC1hbmNob3I9Im1pZGRsZSI+Wk9MTEUgR1JPU1NFPGJyPihEdXJlKTwvdGV4dD48L3N2Zz4=',
    AGG_MOD: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZjMTA3Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGR5PSIuM2VtIiBmaWxsPSIzMzMiIGZvbnQtc2l6ZT0iMTYiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk1JU1RPPGJyPihab2xsZS9GcmFudHVtaSk8L3RleHQ+PC9zdmc+',
    AGG_GOOD: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMTk4NzU0Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGR5PSIuM2VtIiBmaWxsPSJ3aGl0ZSIgZm9udC1zaXplPSIxNiIgdGV4dC1hbmNob3I9Im1pZGRsZSI+R1JVTU9TTzxicj4oRnJpYWJpbGUpPC90ZXh0Pjwvc3ZnPg=='
};

createApp({
    setup() {
        // STATE
        const appVersion = ref('4.2.0');
        const currentView = ref('login');
        const isLoggedIn = ref(false);
        const isLoading = ref(false);
        const isSyncing = ref(false);
        const loginForm = ref({ username: '', password: '' });
        const errorMsg = ref('');
        const user = ref(null);
        
        const audits = ref([]);
        const activeAudit = ref(null);
        const activeCategory = ref('');
        const categories = ref([]); 
        const currentStepIndex = ref(0); 
        
        // COMPUTED
        // [INTEGRO] Nuova logica per etichetta ruolo
        const userRoleLabel = computed(() => {
            if (!user.value || !user.value.groups) return '';
            if (user.value.groups.includes('DAP_AGRONOMI')) return 'Valutatore';
            if (user.value.groups.includes('DAP_AGRICOLTORI')) return 'Agricoltore';
            return 'Utente';
        });

        onMounted(() => {
            const storedUser = localStorage.getItem('dap_user');
            if (storedUser) {
                try {
                    user.value = JSON.parse(storedUser);
                    isLoggedIn.value = true;
                    currentView.value = 'dashboard';
                    loadAuditsList();
                } catch(e) { logout(); }
            }
        });

        const doLogin = () => {
            isLoading.value = true;
            setTimeout(() => {
                if (loginForm.value.username) {
                    const mockUser = { 
                        id: 1, 
                        name: loginForm.value.username, 
                        token: 'tok_X', 
                        groups: ['DAP_AGRONOMI'] // Qui simuliamo il gruppo Agronomo
                    };
                    localStorage.setItem('dap_user', JSON.stringify(mockUser));
                    user.value = mockUser;
                    isLoggedIn.value = true;
                    currentView.value = 'dashboard';
                    loadAuditsList();
                }
                isLoading.value = false;
            }, 500);
        };

        const logout = () => {
            localStorage.removeItem('dap_user');
            user.value = null;
            isLoggedIn.value = false;
            currentView.value = 'login';
        };

        // --- DATA LOAD ---
        const loadAuditsList = () => {
            audits.value = [
                { id: 101, ref: 'AUDIT-26-A', thirdparty_name: 'Podere Val d\'Orcia', date: '2026-02-12', score: 0, status: 'dirty' },
                { id: 102, ref: 'AUDIT-26-B', thirdparty_name: 'Cascina Bio', date: '2026-02-10', score: 65, status: 'synced' }
            ];
        };

        const openAudit = (audit) => {
            /**
             * COSTRUZIONE INDICATORI
             * Struttura basata sui CSV Excel (Sez. 1-6) e PDF Manuale (Sez. 7-8)
             * Pesi per VSA/FSM secondo Manuale Pag 19-20.
             * Pesi per Excel standardizzati a 1.0 (Scala 0-10).
             */
            const mockLines = [
                // =================================================================
                // 1. PRODUZIONE AGRICOLA (da CSV)
                // =================================================================
                {
                    rowid: 10, code: '1.1', label: 'Avvicendamento colturale', category: '1. Produzione',
                    desc: 'Durata della rotazione colturale adottata.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Rotazioni lunghe (> 5 anni)", desc: "Ottimo per la fertilità" },
                        "6": { label: "Rotazioni medie (3-5 anni)", desc: "Standard" },
                        "0": { label: "Monosuccessione o biennale", desc: "Depauperante" }
                    }
                },
                {
                    rowid: 11, code: '1.2', label: 'Copertura del suolo', category: '1. Produzione',
                    desc: 'Il suolo rimane coperto durante l\'inverno?',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Copertura permanente", desc: "Cover crops o pacciamatura" },
                        "5": { label: "Copertura parziale", desc: "Residui colturali" },
                        "0": { label: "Suolo nudo", desc: "Rischio erosione elevato" }
                    }
                },
                {
                    rowid: 12, code: '1.3', label: 'Gestione Fertilizzanti', category: '1. Produzione',
                    desc: 'Tipologia di fertilizzazione prevalente.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Solo Organica (Letame/Compost)", desc: "Ammendante naturale" },
                        "5": { label: "Mista (Organica + Minerale)", desc: "Integrazione" },
                        "0": { label: "Solo Minerale Sintetico", desc: "Impoverimento sostanza organica" }
                    }
                },

                // =================================================================
                // 2. ALLEVAMENTI (da CSV)
                // =================================================================
                {
                    rowid: 20, code: '2.1', label: 'Carico UBA/ha', category: '2. Allevamenti',
                    desc: 'Rapporto bestiame/superficie.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "< 2 UBA/ha", desc: "Sostenibile" },
                        "5": { label: "2 - 4 UBA/ha", desc: "Intensivo" },
                        "0": { label: "> 4 UBA/ha", desc: "Eccessivo (Nitrati)" }
                    }
                },
                {
                    rowid: 21, code: '2.2', label: 'Accesso all\'aperto', category: '2. Allevamenti',
                    desc: 'Gli animali accedono a pascoli o paddock?',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Sì, Pascolo Libero", desc: "Benessere elevato" },
                        "5": { label: "Sì, Paddock limitati", desc: "Benessere sufficiente" },
                        "0": { label: "No, Stabulazione fissa", desc: "Benessere scarso" }
                    }
                },

                // =================================================================
                // 3. APICOLTURA (da CSV)
                // =================================================================
                {
                    rowid: 30, code: '3.1', label: 'Aree Nettarifere', category: '3. Apicoltura',
                    desc: 'Disponibilità di fioriture nell\'raggio di volo.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Abbondanti e scalari", desc: "Fioriture tutto l'anno" },
                        "5": { label: "Presenti ma discontinue", desc: "Fioriture concentrate" },
                        "0": { label: "Assenti / Monocultura", desc: "Nutrizione artificiale necessaria" }
                    }
                },
                {
                    rowid: 31, code: '3.2', label: 'Cera', category: '3. Apicoltura',
                    desc: 'Tipologia di cera utilizzata nei favi.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Cera Biologica/Opercolo", desc: "Priva di residui" },
                        "5": { label: "Cera Convenzionale riciclata", desc: "Rischio accumulo" },
                        "0": { label: "Cera esterna dubbia", desc: "Rischio contaminazione" }
                    }
                },

                // =================================================================
                // 4. QUALITÀ DEL PRODOTTO (da CSV)
                // =================================================================
                {
                    rowid: 40, code: '4.1', label: 'Certificazioni', category: '4. Qualità',
                    desc: 'Certificazioni di qualità possedute.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Biologico / Biodinamico", desc: "Massima garanzia" },
                        "5": { label: "Integrata / DOP / IGP", desc: "Qualità normata" },
                        "0": { label: "Nessuna / Convenzionale", desc: "Standard base" }
                    }
                },
                {
                    rowid: 41, code: '4.2', label: 'Trasformazione', category: '4. Qualità',
                    desc: 'Uso di additivi nella trasformazione.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Nessun additivo sintetico", desc: "Processo naturale" },
                        "5": { label: "Minimo indispensabile", desc: "Ammessi dal disciplinare" },
                        "0": { label: "Uso estensivo conservanti", desc: "Processo industriale" }
                    }
                },

                // =================================================================
                // 5. AGRITURISMO & RISTORAZIONE (da CSV)
                // =================================================================
                {
                    rowid: 50, code: '5.1', label: 'Provenienza Cibo', category: '5. Agriturismo',
                    desc: 'Percentuale materie prime proprie o locali.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "> 80% Proprio/Locale", desc: "Vera filiera corta" },
                        "5": { label: "50% - 80%", desc: "Misto" },
                        "0": { label: "< 50%", desc: "Prevalenza acquisto esterno" }
                    }
                },
                {
                    rowid: 51, code: '5.2', label: 'Stagionalità', category: '5. Agriturismo',
                    desc: 'Il menu rispetta la stagionalità?',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Sì, rigorosamente", desc: "Menu cambia col raccolto" },
                        "5": { label: "Parzialmente", desc: "Alcuni prodotti fuori stagione" },
                        "0": { label: "No", desc: "Menu fisso tutto l'anno" }
                    }
                },

                // =================================================================
                // 6. RESPONSABILITÀ & GIUSTIZIA (da CSV)
                // =================================================================
                {
                    rowid: 60, code: '6.1', label: 'Contratti Lavoro', category: '6. Responsabilità',
                    desc: 'Regolarità contrattuale dei dipendenti.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "100% Regolari", desc: "Pieno rispetto norme" },
                        "5": { label: "Voucher / Stagionali", desc: "Flessibilità normata" },
                        "0": { label: "Irregolarità", desc: "Grave" }
                    }
                },
                {
                    rowid: 61, code: '6.2', label: 'Sicurezza', category: '6. Responsabilità',
                    desc: 'Formazione e DPI per la sicurezza.',
                    input_type: 'SELECT', weight: 1.0, value_measured: null,
                    options: {
                        "10": { label: "Oltre la norma", desc: "Corsi extra, DPI avanzati" },
                        "5": { label: "A norma di legge", desc: "Rispetto base" },
                        "0": { label: "Carenze", desc: "Rischio per i lavoratori" }
                    }
                },

                // =================================================================
                // 7. PROVA DELLA VANGA (Manuale PDF Sez. A - Pag 19)
                // Pesi specifici (Weight): A1=3, A2=3, A3=3, A4=2
                // =================================================================
                {
                    rowid: 70, code: '7.1', label: 'Apparato Radicale', category: '7. Prova Vanga (VS)',
                    desc: 'Osserva lo sviluppo delle radici nella zolla.',
                    input_type: 'SELECT_IMAGE', weight: 3.0, value_measured: null,
                    options: {
                        "0": { label: "Negativa", desc: "Radici a 'L', schiacciate, restrizione severa.", img: IMG_VSA.ROOTS_POOR },
                        "1": { label: "Moderata", desc: "Sviluppo limitato, orizzontale.", img: IMG_VSA.ROOTS_MOD },
                        "2": { label: "Buona", desc: "Sviluppo illimitato, radici fini presenti.", img: IMG_VSA.ROOTS_GOOD }
                    }
                },
                {
                    rowid: 71, code: '7.2', label: 'Suola Lavorazione', category: '7. Prova Vanga (VS)',
                    desc: 'Cerca strati compatti causati dall\'aratro.',
                    input_type: 'SELECT_IMAGE', weight: 3.0, value_measured: null,
                    options: {
                        "0": { label: "Cattiva", desc: "Suola massiva, dura, nessuna fessura.", img: IMG_VSA.PAN_POOR },
                        "1": { label: "Moderata", desc: "Suola presente ma con crepe/pori.", img: IMG_VSA.PAN_MOD },
                        "2": { label: "Buona", desc: "Nessuna suola, struttura friabile.", img: IMG_VSA.PAN_GOOD }
                    }
                },
                {
                    rowid: 72, code: '7.3', label: 'Aggregati', category: '7. Prova Vanga (VS)',
                    desc: 'Distribuzione dimensionale dopo rottura manuale.',
                    input_type: 'SELECT_IMAGE', weight: 3.0, value_measured: null,
                    options: {
                        "0": { label: "Cattiva", desc: "Zolle grosse, dure, spigolose.", img: IMG_VSA.AGG_POOR },
                        "1": { label: "Moderata", desc: "Misto zolle grandi e aggregati piccoli.", img: IMG_VSA.AGG_MOD },
                        "2": { label: "Buona", desc: "Prevalenza aggregati fini e friabili.", img: IMG_VSA.AGG_GOOD }
                    }
                },
                {
                    rowid: 73, code: '7.4', label: 'Lombrichi', category: '7. Prova Vanga (VS)',
                    desc: 'Conta i lombrichi nella zolla (20x20x20 cm).',
                    input_type: 'SELECT', weight: 2.0, value_measured: null,
                    options: {
                        "0": { label: "Pochi (< 4)", desc: "Scarsa attività biologica" },
                        "1": { label: "Moderati (4 - 8)", desc: "Attività media" },
                        "2": { label: "Abbondanti (> 8)", desc: "Attività ottima" }
                    }
                },
                {
                    rowid: 80, code: '7.5', label: 'Stabilità Aggregati', category: '7. Prova Vanga (VS)',
                    desc: 'Test di disgregazione in acqua (Slaking test, 10 min).',
                    input_type: 'SELECT', weight: 1.5, value_measured: null,
                    options: {
                        "0": { label: "Dispersione Totale", desc: "L'aggregato si scioglie (Pessimo)" },
                        "1": { label: "Forte dispersione", desc: "Acqua molto torbida" },
                        "2": { label: "Moderata", desc: "Acqua lattiginosa" },
                        "3": { label: "Leggera", desc: "Leggera lattescenza" },
                        "4": { label: "Nessuna", desc: "Aggregato stabile (Ottimo)" }
                    }
                },
                // =================================================================
                // 8. MISURE DI CAMPO (Manuale PDF Sez. B - Pag 20)
                // Pesi specifici (Weight): A5=1.5, A6=3, A7=2
                // =================================================================
                {
                    rowid: 81, code: '8.2', label: 'Infiltrazione', category: '8. Misure Campo (FSM)',
                    desc: 'Velocità di infiltrazione acqua (mm/h).',
                    input_type: 'SELECT', weight: 3.0, value_measured: null,
                    options: {
                        "0": { label: "< 1 mm/h", desc: "Molto basso (Impermeabile)" },
                        "1": { label: "1 - 36 mm/h", desc: "Medio" },
                        "2": { label: "> 36 mm/h", desc: "Elevato (Ottimo drenaggio)" }
                    }
                },
                {
                    rowid: 82, code: '8.3', label: 'Carbonio Attivo', category: '8. Misure Campo (FSM)',
                    desc: 'Reazione al permanganato (colore).',
                    input_type: 'SELECT', weight: 2.0, value_measured: null,
                    options: {
                        "0": { label: "Scarso", desc: "Colore chiaro (< 0.5)" },
                        "1": { label: "Moderato", desc: "Colore medio (0.5 - 1.0)" },
                        "2": { label: "Buono", desc: "Colore scuro (> 1.0)" }
                    }
                }
            ];

            activeAudit.value = { ...audit, lines: mockLines };
            
            // Inizializza categorie
            const cats = new Set(mockLines.map(l => l.category));
            categories.value = Array.from(cats);
            activeCategory.value = categories.value[0];
            currentStepIndex.value = 0;
            
            calculateScore();
            currentView.value = 'audit_wizard';
        };

        // --- CALCOLO PUNTEGGIO ---
        // Gestisce sia i pesi PDF (A1..A7) sia i punteggi diretti Excel (0-10)
        const calculateScore = () => {
            if (!activeAudit.value) return;
            let totalScore = 0;
            let totalMaxPossible = 0;

            activeAudit.value.lines.forEach(line => {
                const val = parseFloat(line.value_measured);
                
                // Determina il massimo possibile per questa domanda per normalizzare
                let maxValForQuestion = 0;
                if(line.options) {
                    const keys = Object.keys(line.options).map(k => parseFloat(k));
                    maxValForQuestion = Math.max(...keys);
                } else {
                    maxValForQuestion = 10;
                }

                if (!isNaN(val) && line.value_measured !== null) {
                    // Punteggio Pesato = Valore * Peso
                    // Nota: Per le sezioni Excel il peso è 1.0, quindi prende il valore diretto (0, 5, 10)
                    // Per il PDF prende Valore * Peso (es. 2 * 3 = 6)
                    totalScore += (val * line.weight);
                }
                
                totalMaxPossible += (maxValForQuestion * line.weight);
            });

            activeAudit.value.raw_score = totalScore.toFixed(1);
            
            // Punteggio % per la barra
            if (totalMaxPossible > 0) {
                activeAudit.value.local_score = ((totalScore / totalMaxPossible) * 100).toFixed(1);
            } else {
                activeAudit.value.local_score = 0;
            }
        };

        watch(activeAudit, () => calculateScore(), { deep: true });

        // --- NAVIGAZIONE ---
        const activeIndicators = computed(() => {
            if (!activeAudit.value) return [];
            return activeAudit.value.lines.filter(l => l.category === activeCategory.value);
        });

        const currentIndicator = computed(() => {
            const list = activeIndicators.value;
            if (!list || list.length === 0) return null;
            return list[currentStepIndex.value];
        });

        const currentCategoryProgress = computed(() => {
            if (activeIndicators.value.length === 0) return 0;
            return ((currentStepIndex.value + 1) / activeIndicators.value.length) * 100;
        });

        const nextStep = () => {
            if (currentStepIndex.value < activeIndicators.value.length - 1) {
                currentStepIndex.value++;
            } else {
                const currIdx = categories.value.indexOf(activeCategory.value);
                if (currIdx < categories.value.length - 1) {
                    if (confirm("Sezione completata. Passare alla prossima?")) {
                        activeCategory.value = categories.value[currIdx + 1];
                        currentStepIndex.value = 0;
                        window.scrollTo(0,0);
                    }
                } else {
                    if (confirm("Audit completato. Salvare?")) saveAndExit();
                }
            }
        };

        const prevStep = () => {
            if (currentStepIndex.value > 0) {
                currentStepIndex.value--;
            } else {
                const currIdx = categories.value.indexOf(activeCategory.value);
                if (currIdx > 0) {
                    activeCategory.value = categories.value[currIdx - 1];
                    const prevItems = activeAudit.value.lines.filter(l => l.category === activeCategory.value);
                    currentStepIndex.value = Math.max(0, prevItems.length - 1);
                }
            }
        };

        const selectCategory = (cat) => {
            activeCategory.value = cat;
            currentStepIndex.value = 0;
        };

        const saveAndExit = () => {
            activeAudit.value.status = 'dirty';
            const idx = audits.value.findIndex(a => a.id === activeAudit.value.id);
            if(idx !== -1) audits.value[idx] = { ...activeAudit.value, score: activeAudit.value.local_score };
            currentView.value = 'dashboard';
        };

        const syncData = () => { isSyncing.value = true; setTimeout(() => { isSyncing.value=false; alert("Sincronizzazione OK"); }, 800); };
        const getVsaClass = (line, key) => line.value_measured == key ? 'selected' : '';

        return {
            appVersion,currentView, isLoggedIn, isLoading, loginForm, errorMsg, audits, user, userRoleLabel,
            activeAudit, categories, activeCategory, currentStepIndex,
            activeIndicators, currentIndicator, currentCategoryProgress,
            doLogin, logout, openAudit, saveAndExit, syncData,
            nextStep, prevStep, selectCategory, getVsaClass
        };
    }
}).mount('#app');