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
                <button @click="router.push('/visitas')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto">
                    <i class="fas fa-arrow-left"></i> VOLVER AL LISTADO
                </button>
            </div>

            <!-- Loader de Sistema -->
            <div v-if="loading" class="loading-state py-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Consultando base de datos maestra...</p>
            </div>

            <!-- Error de Conexión -->
            <div v-else-if="error" class="error-message-container p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in">
                <i class="fas fa-exclamation-triangle fa-3xl text-red-600 mb-6"></i>
                <h2 class="text-xl font-black text-black uppercase tracking-tighter">Error de Sincronización</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchVisitaDetail" class="btn-primary-action mt-6 px-10">Reintentar</button>
            </div>

            <!-- Contenido Principal -->
            <div v-else-if="visita" class="space-y-8 animate-fade-in pb-20">
                
                <!-- 1. IDENTIDAD DEL PLANTEL -->
                <div class="info-card shadow-premium border-t-8 border-t-red-700">
                    <div class="section-title">
                        <i class="fas fa-school text-red-700"></i> 1. Identidad y Ubicación del Plantel
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-6">
                        <!-- Columna Datos Base -->
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Nombre del Plantel</label>
                                <p class="value-text text-xl leading-none uppercase">{{ visita.nombre_plantel || visita.cliente?.name }}</p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">RFC del Plantel</label>
                                    <p class="value-text font-mono uppercase tracking-widest">{{ visita.rfc_plantel || visita.cliente?.rfc || 'No registrado' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Niveles Educativos del Plantel</label>
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        <span v-for="n in formatLevels(visita.nivel_educativo_plantel || visita.cliente?.nivel_educativo)" :key="n" class="badge-red-outline">
                                            {{ n }} <br><br>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">Estado / Región</label>
                                    <p class="value-text uppercase">{{ visita.estado?.estado || 'No especificado' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Estatus de Prospecto</label>
                                    <p class="font-black text-sm uppercase tracking-wider" :class="visita?.cliente?.tipo === 'CLIENTE' ? 'text-green-600' : 'text-red-700'">
                                        {{ visita?.cliente?.tipo || 'PROSPECTO' }}
                                    </p>
                                </div>
                            </div>

                            <div class="data-row">
                                <label class="label-large">Ubicación Geográfica Capturada</label>
                                <div v-if="visita.latitud" class="flex items-center gap-3 bg-red-50/30 p-4 rounded-2xl border border-red-100 mt-2">
                                    <div class="w-12 h-12 bg-red-700 text-white rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-[10px] font-black text-red-800 uppercase leading-none">Punto GPS en Sesión</p>
                                        <p class="text-xs font-mono font-bold text-red-600 mt-1">{{ visita.latitud }}, {{ visita.longitud }}</p>
                                    </div>
                                    <a :href="`https://www.google.com/maps?q=${visita.latitud},${visita.longitud}`" target="_blank" class="text-[10px] font-black uppercase text-red-700 hover:underline px-3 border-l border-red-100">Ver Mapa</a>
                                </div>
                                <p v-else class="value-text text-slate-300 italic text-sm">Sin coordenadas registradas</p>
                            </div>
                        </div>

                        <!-- Columna Contacto -->
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Nombre del Director / Coordinador</label>
                                <p class="value-text italic leading-relaxed text-sm">{{ visita.direccion_plantel || visita.cliente?.direccion || 'Sin dirección registrada' }}</p>
                            </div>
                            
                            <div class="data-row">
                                <label class="label-large">Representante / Director General</label>
                                <p class="value-text text-lg uppercase">{{ visita.director_plantel || visita.cliente?.contacto || 'No especificado' }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">Celular / Teléfono</label>
                                    <p class="value-text tracking-tighter"><i class="fas fa-phone-alt mr-2 opacity-30"></i>{{ visita.telefono_plantel || visita.cliente?.telefono || 'N/A' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Correo Electrónico</label>
                                    <p class="value-text lowercase text-sm"><i class="fas fa-envelope mr-2 opacity-30"></i>{{ visita.email_plantel || visita.cliente?.email || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DETALLES DE LA VISITA ACTUAL -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 info-card shadow-premium border-t-8 border-t-black">
                        <div class="section-title text-black !border-black/5">
                            <i class="fas fa-handshake text-black"></i> 2. Detalles de la Entrevista (Apertura)
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div class="data-row bg-slate-50 p-5 rounded-2xl border border-slate-100">
                                    <label class="label-large !text-red-700">Persona Entrevistada</label>
                                    <p class="value-text !text-xl !font-black uppercase !color-black">{{ visita.persona_entrevistada || 'N/A' }}</p>
                                    <label class="label-large !text-red-700">Cargo / Puesto de la Persona</label>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">{{ visita.cargo || 'Responsable' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Fecha de Registro Inicial</label>
                                    <p class="value-text">{{ formatDate(visita.fecha) }}</p>
                                </div>
                            </div>
                            <div class="space-y-6">
                                <div class="data-row">
                                    <label class="label-large">Resultado de la Interacción</label>
                                    <div class="mt-2">
                                        <span :class="getOutcomeClass(visita.resultado_visita)" class="status-badge !text-xs !py-2.5 !px-8 shadow-sm">
                                            {{ (visita.resultado_visita || 'seguimiento').toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                               
                            </div>
                        </div>
                    </div>

                    <!-- 3. AGENDA Y SEGUIMIENTO -->
                    <div class="info-card shadow-premium border-t-8 border-t-red-800 bg-slate-900 text-white">
                        <div class="section-title !text-white !border-white/10">
                            <i class="fas fa-calendar-check"></i> 3. Agenda de Seguimiento
                        </div>
                        
                        <div class="space-y-6 mt-4">
                            <div class="p-6 bg-white/5 rounded-3xl border border-white/10">
                                <label class="label-large !text-red-400">Próximo Compromiso</label>
                                <div v-if="visita.proxima_visita_estimada" class="mt-3">
                                    <p class="text-3xl font-black text-white tracking-tighter">{{ formatDate(visita.proxima_visita_estimada) }}</p>
                                    <div class="flex items-center gap-2 mt-4 bg-red-600/20 p-3 rounded-xl border border-red-600/30">
                                        <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center text-xs">
                                            <i class="fas fa-bullseye"></i>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-white/20 italic text-[11px] font-black uppercase tracking-widest mt-4">Sin fecha programada</p>
                            </div>

                        </div>
                    </div>
                </div>
                    <br>
                <!-- 4. EXPEDIENTE DE MATERIALES -->
                <div class="info-card pace-y-6">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-1.5 h-8 bg-black rounded-full"></div>
                        <div class="section-title text-black !border-black/5">
                            <i class="fas fa-handshake text-black"></i> 4. Detalles de la Entrevista (Apertura)
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-8">
                        <!-- APARTADO A: INTERÉS -->
                        <div class="info-card bg1 !p-0 overflow-hidden border border-slate-200 shadow-premium bg-white">
                            <div class="p-6 bg-slate-800 text-white flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-star text-yellow-400"></i>
                                    <h3 class="text-[11px] font-black uppercase tracking-widest">Libros de Interés del Plantel por Serie</h3>
                                </div>
                                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">{{ materialesInteres.length }} Títulos</span>
                            </div>
                            <div class="table-responsive table-shadow-lg border rounded-2xl overflow-hidden bg-white">
                            <table class="w-full border-collapse">
                                <thead class="bg-slate-900">
                                    <tr>
                                        <th class="table-header-enterprise text-left">Material de Interés</th>
                                        <th class="table-header-enterprise text-right">Formato</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="(libro, idx) in materialesInteres" :key="idx" class="hover:bg-slate-50 transition-colors">
                                        <td class="table-cell">
                                            <div class="text-xs font-black text-black uppercase leading-tight">
                                                {{ libro.titulo }}
                                            </div>
                                        </td>
                                        <td class="table-cell text-right">
                                            <span class="text-[9px] text-red-600 font-black uppercase bg-red-50 border border-red-100 px-3 py-1 rounded-lg tracking-widest">
                                                {{ libro.tipo || 'Físico' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!materialesInteres.length">
                                        <td colspan="2" class="px-8 py-12 text-center">
                                            <i class="fas fa-search text-slate-100 text-3xl mb-2 block"></i>
                                            <p class="text-slate-300 font-black uppercase text-[10px] tracking-widest italic">Sin registros de prospección</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                            
                        <!-- APARTADO B: PROMOCIÓN -->
                        <div class="info-card bg2 !p-0 overflow-hidden border border-red-100 shadow-premium bg-white">
                            <div class="p-6 bg-red-900 text-white flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-box-open text-red-300"></i>
                                    <h3 class="text-[11px] font-black uppercase tracking-widest">Muestras de Promoción Entregadas</h3>
                                </div>
                                <span class="text-[10px] font-black text-red-200 uppercase tracking-widest">{{ materialesEntregados.length }} Entrega(s)</span>
                            </div>
                            <div class="table-responsive table-shadow-lg border border-red-100 rounded-2xl overflow-hidden bg-white">
                            <table class="w-full border-collapse">
                                <thead class="bg-red-900">
                                    <tr>
                                        <th class="table-header-enterprise text-white/70 text-left">Muestra Física</th>
                                        <th class="table-header-enterprise text-white/70 text-center">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-red-50">
                                    <tr v-for="(libro, idx) in materialesEntregados" :key="idx" class="hover:bg-red-50/30 transition-colors">
                                        <td class="table-cell">
                                            <div class="text-xs font-black text-black uppercase leading-tight">
                                                {{ libro.titulo }}
                                                <span v-if="libro.edicion" class="text-[9px] text-red-500 ml-1">ED. {{ libro.edicion }}</span>
                                            </div>
                                        </td>
                                        <td class="table-cell text-center">
                                            <span class="text-xl font-black text-red-700 tracking-tighter">
                                                {{ libro.cantidad || '1' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!materialesEntregados.length">
                                        <td colspan="2" class="px-8 py-12 text-center">
                                            <i class="fas fa-box-open text-red-50 text-3xl mb-2 block"></i>
                                            <p class="text-red-200 font-black uppercase text-[10px] tracking-widest italic">Sin entrega de muestras físicas</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                </div>
                <br>

                
                <!-- OBSERVACIONES FINALES (Apertura) -->
                <div v-if="visita.comentarios" class="info-card border-none bg-amber-50 p-10 rounded-[3rem] border border-amber-200 shadow-sm mt-8">
                   
                     <div class="section-title !text-white !border-white/10">
                            <i class="fas fa-calendar-check"></i> 5. Comentarios y Acuerdos de la Sesión
                        </div>
                    <p class="text-base text-slate-700 italic leading-relaxed whitespace-pre-wrap font-medium">"{{ visita.comentarios }}"</p>
                </div>
                    <br>

                <!-- 5. HISTORIAL CRONOLÓGICO DESPLEGABLE (Visitas Subsecuentes) -->
                <div class="info-card space-y-6 mt-16">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-2 h-8 bg-red-700 rounded-full"></div>
                        <div class="section-title text-black !border-black/5">
                            <i class="fas fa-handshake text-black"></i> 6. Historial de Visitas Subsecuentes
                        </div>
                    </div>

                    <div v-if="loadingHistory" class="py-10 text-center animate-pulse">
                        <i class="fas fa-spinner fa-spin text-red-600 text-3xl"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase mt-4">Sincronizando cadena de seguimiento...</p>
                    </div>

                    <div v-else-if="historial.length" class="space-y-4">
                        <div v-for="(h, index) in historial" :key="h.id" class="border border-slate-100 rounded-3xl overflow-hidden shadow-sm">
                            <!-- Header de la tarjeta -->
                            <div @click="toggleExpand(h.id)" 
                                class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-6 cursor-pointer hover:bg-slate-50 transition-colors">
                                
                                <div class="flex items-center gap-6 w-full md:w-auto">
                                    <div class="bg-red-700 w-16 h-16 text-white rounded-3xl flex flex-col items-center justify-center shrink-0 shadow-lg group-hover:scale-105 transition-transform">
                                        <span class="text-[10px] font-black uppercase opacity-60 leading-none mb-1">{{ getMonthShort(h.fecha) }}</span>
                                        <span class="text-2xl font-black leading-none">{{ getDay(h.fecha) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                        </div>
                                        <h4 class="text-xl font-black text-black uppercase tracking-tight truncate max-w-[200px] md:max-w-none">
                                            {{ formatDate(h.fecha) }}
                                        </h4>
                                        <p class="text-[11px] font-bold text-red-600 mt-0.5 uppercase tracking-tighter italic">
                                            Atendido por: {{ h.persona_entrevistada }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-5 w-full md:w-auto justify-between md:justify-end">
                                    <span :class="getOutcomeClass(h.resultado_visita)" class="status-badge !px-5 !py-2 uppercase shadow-sm">
                                        {{ h.resultado_visita }}
                                    </span>
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 group-hover:text-red-600 transition-colors">
                                        <i class="fas fa-chevron-down transition-transform duration-500" 
                                           :class="{'rotate-180 text-red-600': expandedId === h.id}"></i>
                                    </div>
                                </div>

                                <button 
                                    class="absolute btn-primary-action top-4 right-4 text-[10px] font-black uppercase text-red-700 hover:underline px-3">
                                    <i class="fas fa-edit mr-1"></i> Ver más </button>
                            </div>

                            <!-- Contenido Desplegable (Expediente) -->
                            <div v-if="expandedId === h.id" class="p-8 md:p-12 bg-slate-50/40 border-t border-slate-100 animate-fade-in">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                                    <!-- Información Sesión -->
                                    <div class="space-y-8">
                                        <div>
                                            <h5 class="text-black font-black uppercase text-[11px] tracking-widest mb-4 flex items-center gap-2">
                                                <i class="fas fa-id-badge text-red-700"></i> Resumen de la Entrevista
                                            </h5>
                                            <div class="bg-white p-6 rounded-3xl border border-slate-100 space-y-4 shadow-sm">
                                                <div class="grid grid-cols-2 gap-6">
                                                    <div>
                                                        <label class="label-large">Persona Cargo</label>
                                                        <p class="value-text">{{ h.persona_entrevistada }}</p>
                                                    </div>
                                                    <div>
                                                        <label class="label-large">Puesto Oficial</label>
                                                        <p class="value-text">{{ h.cargo }}</p>
                                                    </div>
                                                </div>
                                                <div v-if="h.proxima_visita_estimada" class="pt-3 border-t border-slate-50">
                                                    <label class="label-large">Acuerdo de Cita</label>
                                                    <p class="text-xs font-black text-black uppercase">
                                                        <i class="far fa-calendar-alt mr-1.5 text-red-600"></i>
                                                        {{ formatDate(h.proxima_visita_estimada) }} — <span class="text-red-700 italic">{{ h.proxima_accion }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <h5 class="text-black font-black uppercase text-[11px] tracking-widest mb-4 flex items-center gap-2">
                                                <i class="fas fa-comment-dots text-red-700"></i> Observaciones del Registro
                                            </h5>
                                            <div class="bg-amber-50 p-8 rounded-3xl border border-amber-100 italic text-slate-700 text-sm leading-relaxed font-medium shadow-inner">
                                                "{{ h.comentarios || 'El representante no dejó observaciones escritas en esta sesión.' }}"
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Materiales Desplegables -->
                                    <div class="space-y-8">
                                    <h5 class="text-black font-black uppercase text-[11px] tracking-[0.2em] mb-4 flex items-center gap-2">
                                        <i class="fas fa-book-open text-red-700"></i> Materiales y Muestras Involucradas
                                    </h5>
                                    
                                    <div class="grid grid-cols-1 gap-6">
                                        <div class="table-responsive table-shadow-lg border rounded-2xl overflow-hidden bg-white">
                                            <table class="w-full border-collapse">
                                                <thead class="bg-slate-800">
                                                    <tr>
                                                        <th class="px-5 py-3 text-left text-[9px] font-black text-slate-300 uppercase tracking-widest">Material de Interés</th>
                                                        <th class="px-5 py-3 text-right text-[9px] font-black text-slate-300 uppercase tracking-widest">Formato</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-50">
                                                    <tr v-for="(item, i) in parseMateriales(h.libros_interes).interes" :key="i" class="hover:bg-slate-50/50 transition-colors">
                                                        <td class="px-5 py-4 text-[11px] font-black text-black uppercase leading-tight">
                                                            {{ item.titulo }}
                                                        </td>
                                                        <td class="px-5 py-4 text-right">
                                                            <span class="text-[9px] text-red-600 font-black uppercase bg-red-50 border border-red-100 px-3 py-1 rounded-lg tracking-tighter">
                                                                {{ item.tipo || 'Físico' }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="!parseMateriales(h.libros_interes).interes.length">
                                                        <td colspan="2" class="px-5 py-10 text-center text-[10px] text-slate-300 font-black uppercase tracking-widest italic">Sin intereses registrados</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="table-responsive table-shadow-lg border border-red-100 rounded-2xl overflow-hidden bg-white">
                                            <table class="w-full border-collapse">
                                                <thead class="bg-red-900">
                                                    <tr>
                                                        <th class="px-5 py-3 text-left text-[9px] font-black text-red-200 uppercase tracking-widest">Muestra Entregada</th>
                                                        <th class="px-5 py-3 text-right text-[9px] font-black text-red-200 uppercase tracking-widest">Cantidad</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-red-50">
                                                    <tr v-for="(item, i) in parseMateriales(h.libros_interes).entregado" :key="i" class="hover:bg-red-50/30 transition-colors">
                                                        <td class="px-5 py-4 text-[11px] font-black text-black uppercase leading-tight">
                                                            {{ item.titulo }}
                                                        </td>
                                                        <td class="px-5 py-4 text-right">
                                                            <span class="text-[12px] text-red-700 font-black tracking-tighter">
                                                                {{ item.cantidad }} 
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="!parseMateriales(h.libros_interes).entregado.length">
                                                        <td colspan="2" class="px-5 py-10 text-center text-[10px] text-red-200 font-black uppercase tracking-widest italic">No se entregaron muestras físicas</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-20 bg-white rounded-[3rem] border-2 border-dashed border-slate-100 opacity-60">
                        <i class="fas fa-history text-4xl text-slate-200 mb-4 block"></i>
                        <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest italic">Aún no se han registrado seguimientos posteriores.</p>
                    </div>
                </div>
                <br>

                <div class="info-card border-none bg-slate-100 p-10 rounded-[3rem] border border-slate-200 shadow-sm mt-8 text-center">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-2 h-8 bg-red-700 rounded-full"></div>
                        
                         <div class="section-title text-black !border-black/5">
                            <i class="fas fa-handshake text-black"></i> 7. Registra una nueva Visita
                        </div>
                    </div>

                            <button v-if="visita.resultado_visita === 'seguimiento' && visita.cliente?.tipo !== 'CLIENTE'" 
                                @click="router.push({ name: 'SeguimientoID', params: { id: visita.id } })" 
                                class="w-full btn-primary-action shadow-2xl transition-all active:scale-95">
                                <i class="fas fa-plus-circle mr-2 "></i> Registrar Nueva Visita
                            </button>


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

// Control Acordeón Historial
const expandedId = ref(null);

const fetchVisitaDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/visitas/${route.params.id}`);
        visita.value = response.data;
        
        // Carga historial completo al tener datos del plantel
        if (visita.value.cliente_id || visita.value.nombre_plantel) {
            fetchFullHistory();
        }
    } catch (err) {
        error.value = err.response?.data?.message || "No se pudo recuperar la información de la bitácora.";
    } finally {
        loading.value = false;
    }
};

const fetchFullHistory = async () => {
    loadingHistory.value = true;
    try {
        const term = visita.value.nombre_plantel || visita.value.cliente?.name;
        
        // Enviamos full_history=1 para que el controlador ignore el filtro de es_primera_visita=1
        const response = await axios.get('/visitas', { 
            params: { 
                search: term,
                full_history: 1 
            } 
        });
        
        const dataReceived = response.data.data || response.data;
        
        /**
         * FILTRADO Y ORDENACIÓN:
         * 1. Solo visitas subsecuentes: Number(h.es_primera_visita) === 0 (o false)
         * 2. Ordenadas por ID de forma ascendente (a.id - b.id) para ver la secuencia histórica.
         */
        historial.value = Array.isArray(dataReceived) 
            ? dataReceived
                .filter(h => Number(h.es_primera_visita) === 0)
                .sort((a,b) => a.id - b.id)
            : [];
            
    } catch (e) {
        console.error("Error cargando historial:", e);
    } finally {
        loadingHistory.value = false;
    }
};

const toggleExpand = (id) => {
    expandedId.value = expandedId.value === id ? null : id;
};

const parseMateriales = (raw) => {
    if (!raw) return { interes: [], entregado: [] };
    if (typeof raw === 'object' && !Array.isArray(raw)) return raw;
    try { return JSON.parse(raw); } catch (e) { return { interes: [], entregado: [] }; }
};

const librosRaw = computed(() => {
    if (!visita.value || !visita.value.libros_interes) return null;
    if (typeof visita.value.libros_interes === 'object' && !Array.isArray(visita.value.libros_interes)) return visita.value.libros_interes;
    try { return JSON.parse(visita.value.libros_interes); } catch (e) { return null; }
});

const materialesInteres = computed(() => librosRaw.value?.interes || []);
const materialesEntregados = computed(() => librosRaw.value?.entregado || []);

const formatLevels = (levels) => {
    if (!levels) return ['General'];
    if (Array.isArray(levels)) return levels;
    return levels.split(',').map(l => l.trim()).filter(l => l.length > 0);
};

/**
 * SOLUCIÓN AL BUG DE FECHA (Desfase de zona horaria):
 * Separamos la cadena por '-' y construimos el objeto Date LOCAL.
 */
const formatDate = (dateString) => {
    if (!dateString) return '---';
    const cleanDate = dateString.split('T')[0];
    const [year, month, day] = cleanDate.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const getMonthShort = (dateString) => {
    if (!dateString) return '---';
    const cleanDate = dateString.split('T')[0];
    const [year, month, day] = cleanDate.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    return date.toLocaleDateString('es-ES', { month: 'short' }).replace('.', '').toUpperCase();
};

const getDay = (dateString) => {
    if (!dateString) return '--';
    const cleanDate = dateString.split('T')[0];
    const [year, month, day] = cleanDate.split('-');
    return day;
};

const getOutcomeClass = (outcome) => {
    const base = 'status-badge uppercase font-black tracking-widest ';
    if (outcome === 'compra') return base + 'bg-green-100 text-green-700 border-green-200';
    if (outcome === 'rechazo') return base + 'bg-red-100 text-red-700 border-red-200';
    return base + 'bg-orange-100 text-orange-700 border-orange-200';
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
.info-card { background: white; padding: 40px; border-radius: 40px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #000000; margin-bottom: 30px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 2px; }

.label-large { display: block; font-size: 0.72rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }
.value-text {  color: #be5e5e; line-height: 1.4; }

.status-badge { padding: 6px 16px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0, 0, 0, 0.08); }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

.table-responsive {
    width: 100%;
    background: white;
    transition: all 0.3s ease;
}

.table-shadow-lg {
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
}

/* Cabecera Enterprise (Negra/Gris) */
.table-header-enterprise {
    padding: 14px 20px;
    font-size: 0.65rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: #94a3b8; /* Slate-400 para cabeceras dark */
}

/* Celdas de Datos */
.table-cell {
    padding: 16px 20px;
    vertical-align: middle;
}

/* Tipografía: Títulos en Negro, Datos en Rojo */
.text-black {
    color: #000000;
}

.text-red-700 {
    color: #b91c1c;
}

/* Ajustes de Grid para las versiones de 2 columnas */
.grid {
    display: grid;
    gap: 1.5rem;
}

@media (min-width: 1024px) {
    .lg\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Animación de filas */
tr {
    transition: background-color 0.2s ease;
}

/* Truncado para títulos largos */
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}



.btn-secondary-custom { @apply bg-white border-2 border-slate-200 py-3 px-8 rounded-2xl text-sm font-black transition-all hover:bg-slate-50 text-black; }

.btn-primary-action { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; padding: 14px 45px; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; gap: 10px; }

.badge-red-outline { @apply bg-red-50 text-red-700 text-[9px] font-black uppercase px-2 py-0.5 rounded-lg border border-red-100; }

.badge-blue-outline { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; padding: 4px 10px; border-radius: 10px; }

.text-truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.bg1{ background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
.bg2{ background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); }
</style>