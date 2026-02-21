<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado Dinámico -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 v-if="visita" class="text-2xl md:text-4xl font-black text-black tracking-tight leading-tight break-words">
                        {{ visita.nombre_plantel || visita.cliente?.name || 'Sin nombre' }}
                    </h1>
                    <h1 v-else-if="loading" class="text-2xl font-black text-slate-300 animate-pulse uppercase">Sincronizando información...</h1>
                    <p class="text-xs md:text-sm text-red-600 font-medium mt-1 uppercase tracking-tighter italic">Expediente técnico de prospectación y acuerdos académicos.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto uppercase bg-white border-2 border-slate-200 text-slate-600 py-2.5 px-6 rounded-xl font-black text-[10px] hover:bg-slate-50 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> VOLVER AL LISTADO
                </button>
            </div>

            <!-- Loader de Sistema -->
            <div v-if="loading" class="loading-state py-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Consultando base de datos maestra...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="error-message-container p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in">
                <i class="fas fa-exclamation-triangle fa-3xl text-red-600 mb-6"></i>
                <h2 class="text-xl font-black text-black uppercase tracking-tighter">Error de Sincronización</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchVisitaDetail" class="btn-primary-action mt-6 px-10">Reintentar</button>
            </div>

            <!-- Contenido Principal -->
            <div v-else-if="visita" class="space-y-8 animate-fade-in pb-20">
                
                <!-- 1. IDENTIDAD DEL PLANTEL -->
                <div class="info-card shadow-premium border-t-8 border-t-red-700 bg-white p-10 rounded-[2.5rem] border border-slate-100">
                    <div class="section-title">
                        <i class="fas fa-school text-red-700"></i> 1. Identidad y Ubicación del Plantel
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-6">
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Nombre del Plantel</label>
                                <p class="value-text text-xl leading-none uppercase font-black">{{ visita.nombre_plantel || visita.cliente?.name }}</p>
                            </div>

                             <div class="data-row">
                                    <label class="label-large">Estatus Actual</label>
                                    <p class="font-black text-sm uppercase tracking-wider" :class="visita?.cliente?.tipo === 'CLIENTE' ? 'text-green-600' : 'text-red-700'">
                                        {{ visita?.cliente?.tipo || 'PROSPECTO' }}
                                    </p>
                                </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">RFC del Plantel</label>
                                    <p class="value-text font-mono uppercase tracking-widest">{{ visita.rfc_plantel || visita.cliente?.rfc || 'No registrado' }}</p>
                                </div>

                                <div class="data-row">
                                <label class="label-large">Ubicación Geográfica (GPS)</label>
                                <div v-if="visita.latitud" class="flex items-center gap-3 bg-red-50/30 p-4 rounded-2xl border border-red-100 mt-2">
                                    <div class="w-12 h-12 bg-red-700 text-white rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-mono font-bold text-red-600 truncate">{{ visita.latitud }}, {{ visita.longitud }}</p>
                                    </div>
                                    <a :href="`https://www.google.com/maps?q=${visita.latitud},${visita.longitud}`" target="_blank" class="text-[10px] font-black uppercase text-red-700 hover:underline border-l border-red-100 pl-3">Ver Mapa</a>
                                </div>
                                <p v-else class="value-text text-slate-300 italic text-sm">Sin coordenadas registradas</p>
                            </div>

                                <div class="data-row">
                                    <label class="label-large">Niveles Educativos</label>
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        <span v-for="n in formatLevels(visita.nivel_educativo_plantel || visita.cliente?.nivel_educativo)" :key="n" class="badge-red-outline">
                                            {{ n }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>  

                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Celular / Teléfono</label>
                                <p class="value-text tracking-tighter"><i class="fas fa-phone-alt mr-2 opacity-30"></i>{{ visita.telefono_plantel || visita.cliente?.telefono || 'N/A' }}</p>
                            </div>
                            <div class="data-row">
                                <label class="label-large">Correo Electrónico</label>
                                <p class="value-text text-sm" style="text-transform: none !important;">
                                    <i class="fas fa-envelope mr-2 opacity-30"></i>
                                    {{ (visita.email_plantel || visita.cliente?.email || 'N/A').toLowerCase() }}
                                </p>
                            </div>
                            <div class="data-row">
                                <label class="label-large">Dirección</label>
                                <p class="value-text uppercase text-xs leading-tight">{{ visita.direccion_plantel || 'No especificada' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HISTORIAL CRONOLÓGICO DESPLEGABLE -->
                <div class="info-card space-y-6 mt-16 bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-premium">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-2 h-8 bg-red-700 rounded-full"></div>
                        <div class="section-title text-black !border-b-0 !mb-0">
                            <i class="fas fa-history text-black"></i> 2. Historial de Interacciones Académicas
                        </div>
                    </div>

                    <div v-if="loadingHistory" class="py-10 text-center animate-pulse">
                        <i class="fas fa-spinner fa-spin text-red-600 text-3xl"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase mt-4">Recuperando cadena histórica...</p>
                    </div>

                    <div v-else-if="historial.length" class="space-y-6">
                        <div v-for="(h, index) in historial" :key="h.id" class="border border-slate-100 rounded-[2.5rem] overflow-hidden shadow-sm relative group bg-white">
                            <div @click="toggleExpand(h.id)" class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-6 transition-colors cursor-pointer hover:bg-slate-50/50">
                                <div class="flex items-center gap-6 w-full md:w-auto">
                                    <div class="min-w-0">
                                        <p class="text-[8px] bld font-black uppercase tracking-[0.2em] mb-1" :class="h.es_primera_visita ? 'text-blue-600' : 'text-purple-600'">
                                            {{ h.es_primera_visita ? 'Apertura de Prospecto' : 'Seguimiento Técnico' }}
                                        </p>
                                        <h4 class="text-xl font-black text-black uppercase tracking-tight truncate">
                                            {{ formatDate(h.fecha) }}
                                        </h4>
                                        <p class="text-[11px] font-bold text-slate-400 mt-1 uppercase tracking-tighter">
                                            <i class="fas fa-user-circle mr-1"></i> {{ h.persona_entrevistada }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-4 w-full md:w-auto justify-between md:justify-end">
                                    <button 
                                        v-if="(h.modificaciones_realizadas || 0) < 1"
                                        @click.stop="router.push({ name: 'VisitaEdit', params: { id: h.id } })"
                                        class="btn-secondary group-hover:bg-red-700 group-hover:text-white transition-all"
                                    >
                                        <i class="fas fa-pencil-alt mr-1.5"></i> AJUSTAR
                                    </button>
                                    <br/>
                                    <br/>
                                    <!-- BOTÓN VER MÁS -->
                                    <button class="btn-secondary group-hover:bg-red-700 group-hover:text-white transition-all">
                                        <span class="mr-2">{{ expandedId === h.id ? 'CERRAR' : 'VER MÁS' }}</span>
                                        <i class="fas transition-transform duration-500" :class="expandedId === h.id ? 'fa-times rotate-90' : 'fa-chevron-right'"></i>
                                    </button>
<br/><br/>
                                    <span :class="getOutcomeClass(h.resultado_visita)" class="status-badge !px-6 !py-2.5 uppercase shadow-sm text-[10px] font-black">
                                        {{ h.resultado_visita }}
                                    </span>
                                </div>
                            </div>

                            <!-- Contenido Desplegable Rediseñado -->
                            <div v-if="expandedId === h.id" class="p-8 md:p-10 bg-slate-50/50 border-t border-slate-100 animate-fade-in">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                                    
                                    <!-- BLOQUE IZQUIERDO: DETALLES Y COMENTARIOS (5/12) -->
                                    <div class="lg:col-span-5 space-y-8">
                                        <div>
                                            <h5 class="text-slate-900 font-black uppercase text-[10px] tracking-widest mb-4 flex items-center gap-2">
                                                <i class="fas fa-info-circle text-red-700 bld"></i> Información de la Sesión
                                            </h5>
                                            <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm grid grid-cols-2 gap-6">
                                                <div>
                                                    <label class="label-xs-grey bld">Contacto Directo</label>
                                                    <p class="value-text-dark">{{ h.persona_entrevistada }}</p>
                                                </div>
                                                <div>
                                                    <label class="label-xs-greyv bld">Cargo / Puesto</label>
                                                    <p class="value-text-dark">{{ h.cargo }}</p>
                                                </div>
                                                <div v-if="h.proxima_visita_estimada" class="col-span-2 pt-4 border-t border-slate-50">
                                                    <label class="label-xs-grey bld">Siguiente Compromiso</label>
                                                    <p class="text-[11px] font-black text-red-700 uppercase flex items-center gap-2">
                                                        <i class="fas fa-calendar-check"></i>
                                                        {{ formatDate(h.proxima_visita_estimada) }}
                                                        <span class="text-slate-400 font-bold ml-1">— {{ h.proxima_accion }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <h5 class="text-slate-900 font-black uppercase text-[10px] tracking-widest mb-4 flex items-center gap-2">
                                                <i class="fas fa-comment-dots text-red-700 bld"></i> Observaciones del Representante
                                            </h5>
                                            <div class="bg-amber-50/50 p-6 md:p-8 rounded-[2rem] border border-amber-200/60 shadow-sm relative">
                                                <i class="fas fa-quote-left absolute top-4 left-4 text-amber-200 text-xl opacity-50"></i>
                                                <p class="text-slate-700 text-sm font-medium italic leading-relaxed pl-4">
                                                    {{ h.comentarios || 'No se registraron observaciones adicionales para esta visita.' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- BLOQUE DERECHO: TABLAS DE MATERIALES (7/12) -->
                                    <div class="lg:col-span-7 space-y-8">
                                        <h5 class="material-block-title">
                                            <i class="fas fa-briefcase"></i> Control de Materiales
                                        </h5>
                                        
                                        <div class="space-y-8">

                                            <!-- TABLA A: LIBROS DE INTERÉS -->
                                            <div class="material-table-wrapper">
                                                <!-- Título de tabla en rojo vino -->
                                                <div class="material-table-header">
                                                    <div class="flex items-center gap-3">
                                                        <div class="material-table-icon">
                                                            <i class="fas fa-star"></i>
                                                        </div>
                                                        <div>
                                                            <p class="material-table-label">Sección A</p>
                                                            <h3 class="material-table-title">Libros de Interés Académico</h3>
                                                        </div>
                                                    </div>
                                                    <span class="material-table-count">
                                                        {{ parseMateriales(h.libros_interes).interes?.length || 0 }} registros
                                                    </span>
                                                </div>

                                                <!-- Tabla con fondo blanco y filas definidas -->
                                                <div class="overflow-x-auto">
                                                    <table class="material-table">
                                                        <thead>
                                                            <tr>
                                                                <th class="material-th" style="width: 50px;">#</th>
                                                                <th class="material-th">Título del Material</th>
                                                                <th class="material-th">Serie</th>
                                                                <th class="material-th text-center" style="width: 110px;">Formato</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(item, i) in parseMateriales(h.libros_interes).interes" :key="i" class="material-tr">
                                                                <td class="material-td material-td-num">{{ i + 1 }}</td>
                                                                <td class="material-td">
                                                                    <p class="material-cell-title">{{ item.titulo }}</p>
                                                                </td>
                                                                <td class="material-td">
                                                                    <p class="material-cell-sub">{{ item.serie_nombre || '—' }}</p>
                                                                </td>
                                                                <td class="material-td text-center">
                                                                    <span class="material-badge-a">{{ item.tipo || 'Físico' }}</span>
                                                                </td>
                                                            </tr>

                                                            <!-- Fila vacía decorativa si hay menos de 3 items -->
                                                            <tr v-for="n in Math.max(0, 3 - (parseMateriales(h.libros_interes).interes?.length || 0))" :key="'empty-a-' + n" class="material-tr-empty">
                                                                <td class="material-td material-td-num text-slate-200">{{ (parseMateriales(h.libros_interes).interes?.length || 0) + n }}</td>
                                                                <td class="material-td" colspan="3">
                                                                    <span class="material-empty-cell">—</span>
                                                                </td>
                                                            </tr>

                                                            <tr v-if="!parseMateriales(h.libros_interes).interes?.length">
                                                                <td colspan="4" class="material-empty-row">
                                                                    <i class="fas fa-inbox mr-2 opacity-40"></i>
                                                                    Sin registros de interés académico
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="material-tfoot">
                                                                <td colspan="4" class="material-tf-cell">
                                                                    Total de títulos de interés: <strong>{{ parseMateriales(h.libros_interes).interes?.length || 0 }}</strong>
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- TABLA B: MUESTRAS ENTREGADAS -->
                                            <div class="material-table-wrapper">
                                                <!-- Título de tabla en rojo vino -->
                                                <div class="material-table-header">
                                                    <div class="flex items-center gap-3">
                                                        <div class="material-table-icon">
                                                            <i class="fas fa-box-open"></i>
                                                        </div>
                                                        <div>
                                                            <p class="material-table-label">Sección B</p>
                                                            <h3 class="material-table-title">Muestras de Promoción Entregadas</h3>
                                                        </div>
                                                    </div>
                                                    <span class="material-table-count">
                                                        {{ parseMateriales(h.libros_interes).entregado?.length || 0 }} registros
                                                    </span>
                                                </div>

                                                <!-- Tabla con fondo blanco y filas definidas -->
                                                <div class="overflow-x-auto">
                                                    <table class="material-table">
                                                        <thead>
                                                            <tr>
                                                                <th class="material-th" style="width: 50px;">#</th>
                                                                <th class="material-th">Descripción de Muestra</th>
                                                                <th class="material-th text-center" style="width: 120px;">Cantidad</th>
                                                                <th class="material-th text-center" style="width: 110px;">Estado</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(item, i) in parseMateriales(h.libros_interes).entregado" :key="i" class="material-tr">
                                                                <td class="material-td material-td-num">{{ i + 1 }}</td>
                                                                <td class="material-td">
                                                                    <p class="material-cell-title">{{ item.titulo }}</p>
                                                                </td>
                                                                <td class="material-td text-center">
                                                                    <span class="material-qty">{{ item.cantidad }}</span>
                                                                </td>
                                                                <td class="material-td text-center">
                                                                    <span class="material-badge-b">Entregado</span>
                                                                </td>
                                                            </tr>

                                                            <!-- Filas vacías decorativas -->
                                                            <tr v-for="n in Math.max(0, 3 - (parseMateriales(h.libros_interes).entregado?.length || 0))" :key="'empty-b-' + n" class="material-tr-empty">
                                                                <td class="material-td material-td-num text-slate-200">{{ (parseMateriales(h.libros_interes).entregado?.length || 0) + n }}</td>
                                                                <td class="material-td" colspan="3">
                                                                    <span class="material-empty-cell">—</span>
                                                                </td>
                                                            </tr>

                                                            <tr v-if="!parseMateriales(h.libros_interes).entregado?.length">
                                                                <td colspan="4" class="material-empty-row">
                                                                    <i class="fas fa-box mr-2 opacity-40"></i>
                                                                    No se entregaron muestras físicas
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="material-tfoot">
                                                                <td colspan="4" class="material-tf-cell">
                                                                    Total de muestras entregadas: <strong>{{ parseMateriales(h.libros_interes).entregado?.reduce((acc, i) => acc + (Number(i.cantidad) || 0), 0) || 0 }}</strong> unidades
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-20 bg-slate-50 rounded-[3rem] border-2 border-dashed border-slate-100 opacity-60">
                        <i class="fas fa-history text-4xl text-slate-200 mb-4 block"></i>
                        <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest italic">Iniciando cadena de seguimiento técnico.</p>
                    </div>
                </div>

                <!-- 3. TABLA DE AUDITORÍA DE AJUSTES -->
                <div class="info-card shadow-premium border-t-8 border-t-slate-800 bg-white p-0 rounded-[2.5rem] border border-slate-100 overflow-hidden mt-16">
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Bitácora de Ajustes Técnicos</h2>
                        </div>
                        <span v-if="allLogs.length" class="text-[9px] font-black bg-red-600 text-white px-3 py-1 rounded-full uppercase tracking-widest">
                            {{ allLogs.length }} MODIFICACIONES
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead class="bg-slate-50 text-slate-400 uppercase text-[9px] font-black tracking-[0.2em] border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-left w-64">Intervención Editada</th>
                                    <th class="px-6 py-4 text-left">Motivo de la Modificación</th>
                                    <th class="px-6 py-4 text-left w-56">Responsable</th>
                                    <th class="px-8 py-4 text-right w-48">Sincronización</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                <tr v-for="(log, index) in allLogs" :key="log.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black text-red-700 uppercase tracking-tighter">
                                                {{ log.visit_type === 'primera' ? 'Primera Visita' : 'Seguimiento' }}
                                                <br>
                                            </span>
                                            <span class="text-[9px] font-bold text-slate-400 uppercase mt-1">{{ formatDateShort(log.visit_date) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6">
                                        <p class="text-[12px] font-bold text-slate-700 italic leading-relaxed uppercase">
                                            {{ log.motivo_cambio || 'SIN JUSTIFICACIÓN TÉCNICA' }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-6">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 bg-slate-900 text-white rounded-full flex items-center justify-center text-[8px] font-black">
                                            </div>
                                            <span class="text-[11px] font-black text-slate-800 uppercase tracking-tight">
                                                {{ log.user?.name || 'Representante' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-[11px] font-black text-slate-800 uppercase">{{ formatDateOnly(log.created_at) }}</span>
                                            <br>
                                            <span class="text-[9px] font-bold text-slate-400 mt-0.5 tracking-tighter">{{ formatTimeOnly(log.created_at) }}</span>
                                        </div>
                                    </td>
                                </tr>
                                
                                <tr v-if="allLogs.length === 0">
                                    <td colspan="5" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center opacity-40">
                                            <i class="fas fa-shield-alt text-4xl text-slate-300 mb-4"></i>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Expediente con integridad original</p>
                                            <p class="text-[9px] text-slate-400 mt-1 italic">No se han registrado ajustes posteriores al registro inicial.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. PRÓXIMO COMPROMISO -->
                <div class="info-card border-none bg-slate-900 p-10 rounded-[3rem] shadow-xl mt-8 text-center text-white relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-500 mb-8">Agenda de Retorno Estimada</h3>
                        
                        <div v-if="proximoCompromisoFinal" class="bg-white/5 p-8 rounded-[2.5rem] border border-white/10 max-w-lg mx-auto backdrop-blur-sm">
                            <p class="text-4xl font-black text-red-500 tracking-tighter">{{ formatDate(proximoCompromisoFinal.fecha) }}</p>
                            <div class="mt-4 inline-flex items-center gap-2 bg-red-700/20 text-red-400 px-6 py-2 rounded-full border border-red-700/30">
                                <i class="fas fa-bullseye text-[10px]"></i>
                            </div>
                        </div>
                        <p v-else class="text-slate-500 italic text-sm mb-6">Sin fecha de seguimiento programada</p>

                        <button v-if="visita.resultado_visita === 'seguimiento' && visita.cliente?.tipo !== 'CLIENTE'" 
                            @click="router.push({ name: 'SeguimientoID', params: { id: visita.id } })" 
                            class="btn-primary mt-8 mx-auto !px-20 !py-5 shadow-2xl shadow-red-900/40">
                            <i class="fas fa-plus-circle mr-2"></i> REGISTRAR SEGUIMIENTO
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../axios';

const route = useRoute();
const router = useRouter();
const visita = ref(null);
const historial = ref([]);
const loading = ref(true);
const loadingHistory = ref(true);
const error = ref(null);
const expandedId = ref(null);

const fetchVisitaDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/visitas/${route.params.id}`);
        visita.value = response.data;
        
        if (visita.value.cliente_id) {
            fetchFullHistory(visita.value.cliente_id);
        }
    } catch (err) {
        error.value = "Expediente no localizado en el servidor central.";
    } finally {
        loading.value = false;
    }
};

const fetchFullHistory = async (clienteId) => {
    loadingHistory.value = true;
    try {
        const response = await axios.get('/visitas', { 
            params: { cliente_id: clienteId, full_history: 1, include_logs: 1 } 
        });
        
        const dataReceived = response.data.data || response.data;
        historial.value = Array.isArray(dataReceived) 
            ? dataReceived.sort((a,b) => a.id - b.id)
            : [];
            
    } catch (e) {
        console.error("Fallo al sincronizar historial:", e);
    } finally {
        loadingHistory.value = false;
    }
};

const allLogs = computed(() => {
    const aggregated = [];
    historial.value.forEach(v => {
        if (v.logs && v.logs.length) {
            v.logs.forEach(l => {
                aggregated.push({
                    ...l,
                    visit_type: v.es_primera_visita ? 'primera' : 'seguimiento',
                    visit_date: v.fecha
                });
            });
        }
    });
    return aggregated.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const proximoCompromisoFinal = computed(() => {
    const conAgenda = [...historial.value]
        .filter(h => h.proxima_visita_estimada)
        .sort((a, b) => b.id - a.id);

    if (conAgenda.length > 0) return { fecha: conAgenda[0].proxima_visita_estimada, accion: conAgenda[0].proxima_accion };
    return null;
});

const toggleExpand = (id) => expandedId.value = expandedId.value === id ? null : id;

const parseMateriales = (raw) => {
    if (!raw) return { interes: [], entregado: [] };
    if (typeof raw === 'object' && !Array.isArray(raw)) return raw;
    try { return JSON.parse(raw); } catch (e) { return { interes: [], entregado: [] }; }
};

const formatLevels = (levels) => {
    if (!levels) return ['General'];
    if (Array.isArray(levels)) return levels;
    return levels.split(',').map(l => l.trim()).filter(l => l.length > 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    const [year, month, day] = dateString.split('T')[0].split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatDateOnly = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatDateShort = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: '2-digit' });
};

const formatTimeOnly = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

const getMonthShort = (dateString) => {
    if (!dateString) return '---';
    const [year, month, day] = dateString.split('T')[0].split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('es-ES', { month: 'short' }).replace('.', '').toUpperCase();
};

const getDay = (dateString) => dateString ? dateString.split('T')[0].split('-')[2] : '--';

const getOutcomeClass = (outcome) => {
    const base = 'status-badge uppercase font-black tracking-widest ';
    if (outcome === 'compra') return base + 'bg-green-100 text-green-700 border-green-200';
    if (outcome === 'rechazo') return base + 'bg-red-100 text-red-700 border-red-200';
    return base + 'bg-orange-100 text-orange-700 border-orange-200';
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
.info-card { background: white; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #000; margin-bottom: 30px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 2px; }
.label-large { display: block; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; color: #94a3b8; margin-bottom: 6px; letter-spacing: 0.1em; }
.label-xs-grey { @apply text-[9px] font-black uppercase text-slate-400 block mb-1 tracking-widest; }
.value-text { color: #be5e5e; line-height: 1.4; font-weight: 800; }
.value-text-dark { @apply text-slate-800 font-black uppercase text-xs; }
.status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.6rem; font-weight: 900; display: inline-block; border: 1px solid transparent; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0, 0, 0, 0.08); }

.btn-see-more {
    @apply bg-slate-900 text-white py-2 px-6 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center shadow-lg active:scale-95 transition-all;
}
.btn-edit-inline { @apply bg-white border-2 border-slate-100 text-slate-400 py-1.5 px-4 rounded-xl hover:bg-red-50 hover:text-red-700 hover:border-red-100 transition-all cursor-pointer text-[10px] font-black uppercase; }
.badge-red-outline { @apply bg-red-50 text-red-700 text-[9px] font-black uppercase px-2 py-0.5 rounded-lg border border-red-100; }
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.bld{ display: block; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; color: #7f8c9c; margin-bottom: 6px; letter-spacing: 0.1em; }

.btn-secondary {
    padding: 8px 15px; background: white; border: 1px solid #cbd5e1; border-radius: 12px;
    color: #64748b; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; cursor: pointer;
}
.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }
.btn-primary-action { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; padding: 14px 45px; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; transition: 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; gap: 10px; }


.material-block-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    color: #1e293b;          /* negro */
    margin-bottom: 4px;
}
.material-block-title i {
    color: #7f1d1d;          /* rojo vino */
}

/* Contenedor de cada tabla */
.material-table-wrapper {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}

/* Cabecera de la tabla (donde va el título en rojo vino) */
.material-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: #ffffff;
    border-bottom: 2px solid #7f1d1d;   /* línea inferior rojo vino */
}

.material-table-icon {
    width: 36px;
    height: 36px;
    background: #7f1d1d;               /* rojo vino */
    color: #ffffff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
}

/* Etiqueta pequeña "Sección A / B" — negro */
.material-table-label {
    font-size: 8px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    color: #0f172a;          /* negro */
    margin-bottom: 2px;
}

/* Título grande de la tabla — rojo vino */
.material-table-title {
    font-size: 13px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #7f1d1d;          /* rojo vino */
    line-height: 1;
}

/* Contador de registros */
.material-table-count {
    font-size: 9px;
    font-weight: 900;
    text-transform: uppercase;
    color: #7f1d1d;          /* rojo vino */
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 20px;
    padding: 4px 10px;
    letter-spacing: 0.1em;
}

/* Tabla en sí */
.material-table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
}

/* Encabezados de columna — fondo levemente gris, texto NEGRO */
.material-th {
    padding: 10px 16px;
    font-size: 8px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: #000000;          /* subtítulos en negro */
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-right: 1px solid #f1f5f9;
    text-align: left;
    white-space: nowrap;
}
.material-th:last-child { border-right: none; }

/* Filas normales */
.material-tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}
.material-tr:hover { background: #fef2f2; }   /* hover suave rojo muy claro */
.material-tr:last-child { border-bottom: none; }

/* Filas vacías decorativas */
.material-tr-empty {
    border-bottom: 1px solid #f8fafc;
}

/* Celdas */
.material-td {
    padding: 12px 16px;
    vertical-align: middle;
    border-right: 1px solid #f1f5f9;
    font-size: 11px;
}
.material-td:last-child { border-right: none; }

/* Celda de número */
.material-td-num {
    font-size: 9px;
    font-weight: 900;
    color: #7f1d1d;          /* rojo vino */
    width: 50px;
    text-align: center;
    background: #fef2f2;
    border-right: 1px solid #fecaca;
}

/* Texto principal de celda — rojo vino */
.material-cell-title {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    color: #7f1d1d;          /* rojo vino */
    line-height: 1.3;
}

/* Texto secundario de celda (serie) — negro suave */
.material-cell-sub {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 0.05em;
}

/* Celda vacía (guión) */
.material-empty-cell {
    font-size: 10px;
    font-weight: 700;
    color: #cbd5e1;
}

/* Badge formato (Físico, Digital, etc.) — Sección A */
.material-badge-a {
    display: inline-block;
    font-size: 8px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #7f1d1d;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 3px 8px;
}

/* Badge estado (Entregado) — Sección B */
.material-badge-b {
    display: inline-block;
    font-size: 8px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #166534;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    padding: 3px 8px;
}

/* Cantidad numérica */
.material-qty {
    font-size: 16px;
    font-weight: 900;
    color: #7f1d1d;          /* rojo vino */
    line-height: 1;
}

/* Fila vacía con mensaje */
.material-empty-row {
    padding: 24px 16px;
    text-align: center;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #94a3b8;
    font-style: italic;
}

/* Pie de tabla */
.material-tfoot {
    background: #fef2f2;
    border-top: 1.5px solid #fecaca;
}
.material-tf-cell {
    padding: 10px 16px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #7f1d1d;          /* rojo vino */
    text-align: right;
}
.material-tf-cell strong {
    font-weight: 900;
    font-size: 11px;
}
</style>