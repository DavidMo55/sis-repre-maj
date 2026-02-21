<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-black uppercase tracking-tighter">Ingreso de Pedidos</h1>
                    <p class="text-xs md:text-sm text-red-600 font-bold uppercase tracking-widest mt-1">Gestión logística avanzada vinculada a la ficha del cliente.</p>
                </div>
                <button @click="router.push('/pedidos')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto uppercase">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al Historial
                </button>
            </div>
            
            <form @submit.prevent="submitOrder" class="space-y-6">

                <!-- 1. INFORMACIÓN DEL CLIENTE -->
                <div class="form-section shadow-premium border-t-4 border-t-black !overflow-visible" :class="{'border-red-500 ring-1 ring-red-100': errors.clientId}">
                    <div class="section-title text-black">
                        <i class="fas fa-user-circle text-red-700"></i> 1. Información del Cliente
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group relative">
                            <label class="label-style">Seleccionar Plantel / Distribuidor *</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    class="form-input pl-10 font-bold lbb uppercase"  
                                    :class="{'border-red-600 bg-red-50': errors.clientId}"
                                    placeholder="BUSCAR POR NOMBRE..." 
                                    v-model="orderForm.clientName"
                                    @input="searchClients"
                                    autocomplete="off"
                                    required
                                >
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                            </div>
                            <ul v-if="clientSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                <li v-for="client in clientSuggestions" :key="client.id" @click="selectClient(client)" class="p-3 border-b last:border-0 hover:bg-red-50 transition-colors cursor-pointer">
                                    <div class="text-xs font-black text-black uppercase">{{ client.name }}</div>
                                    <div class="text-[9px] text-red-500 uppercase font-black tracking-widest mt-1">{{ client.tipo }}</div>
                                </li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label class="label-style">Prioridad de Atención:</label>
                            <select v-model="orderForm.prioridad" class="form-input font-bold text-red-700 uppercase">
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. RECEPCIÓN Y LOGÍSTICA -->
                <div class="form-section shadow-premium border-t-4 border-t-black !overflow-visible">
                    <div class="section-title text-black">
                        <i class="fas fa-truck text-slate-800"></i> 2. Recepción y Logística de Envío
                    </div>
                    
                    <div class="space-y-6">
                        <div class="form-group">
                            <label class="label-style">Método de Envío:</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                                <label class="shipping-card" :class="{'active': orderForm.logistics.deliveryOption === 'paqueteria'}">
                                    <input type="radio" value="paqueteria" v-model="orderForm.logistics.deliveryOption" class="hidden">
                                    <i class="fas fa-box"></i>
                                    <span>Paquetería</span>
                                </label>
                                <label class="shipping-card" :class="{'active': orderForm.logistics.deliveryOption === 'recoleccion'}">
                                    <input type="radio" value="recoleccion" v-model="orderForm.logistics.deliveryOption" class="hidden">
                                    <i class="fas fa-warehouse"></i>
                                    <span>Recolección</span>
                                </label>
                                <label class="shipping-card" :class="{'active': orderForm.logistics.deliveryOption === 'entrega'}">
                                    <input type="radio" value="entrega" v-model="orderForm.logistics.deliveryOption" class="hidden">
                                    <i class="fas fa-shuttle-van"></i>
                                    <span>Entrega Directa</span>
                                </label>
                            </div>

                            <div class="mt-6 space-y-4 animate-fade-in">
                                <div v-if="orderForm.logistics.deliveryOption === 'paqueteria'">
                                    <label class="label-mini">Empresa de Paquetería sugerida por el cliente:</label>
                                    <input v-model="orderForm.logistics.paqueteria_nombre" type="text" required minlength="3" class="form-input border-red-200 text-red-700 font-bold uppercase" placeholder="DHL, FEDEX, ETC.">
                                </div>
                                <div v-if="['recoleccion', 'entrega'].includes(orderForm.logistics.deliveryOption)">
                                    <label class="label-mini">Instrucciones Logísticas:</label>
                                    <textarea v-model="orderForm.logistics.comentarios_logistica" minlength="10" class="form-input text-red-600 font-medium uppercase" rows="2" required placeholder="NOTAS PARA ALMACÉN..."></textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="border-red-100">

                        <div class="bg-red-50/20 p-5 rounded-3xl border border-red-100 flex flex-wrap gap-6 items-center">
                            <label class="text-[10px] font-black text-red-800 uppercase tracking-widest">Origen de Datos de Envío:</label>
                            <div class="flex flex-wrap gap-4 md:gap-8">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="cliente" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600" :disabled="!orderForm.clientId">
                                    <span class="text-[11px] font-black text-red-700 uppercase">Datos del Cliente</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="existente" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600">
                                    <span class="text-[11px] font-black text-red-700 uppercase">Mis Receptores</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="nuevo" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600">
                                    <span class="text-[11px] font-black text-red-700 uppercase">Ingresar datos</span>
                                </label>
                            </div>
                        </div>

                        <!-- 2.A: BUSCAR RECEPTOR PROPIO -->
                        <div v-if="orderForm.receiverType === 'existente'" class="animate-fade-in space-y-4">
                            <div class="form-group relative">
                                <label class="label-style">Buscar en mi agenda personal (Nombre, RFC o Teléfono)</label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        class="form-input pl-10 font-bold uppercase border-red-200" 
                                        v-model="searchReceiverQuery" 
                                        @input="searchExistingReceivers"
                                        placeholder="SOLO REGISTROS PROPIOS..."
                                        autocomplete="off"
                                    >
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                                    <i v-if="searchingExisting" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                                </div>
                                <ul v-if="receiverSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                    <li v-for="rec in receiverSuggestions" :key="rec.id" @click="selectExistingReceiver(rec)" class="p-3 border-b last:border-0 hover:bg-red-50 cursor-pointer">
                                        <div class="text-xs font-black text-slate-800 uppercase">{{ rec.nombre }}</div>
                                        <div class="flex gap-4 mt-1">
                                            <span class="text-[8px] font-bold text-red-500 uppercase">RFC: {{ rec.rfc }}</span>
                                            <span class="text-[8px] font-bold text-slate-400 uppercase">TEL: {{ rec.telefono }}</span>
                                        </div>
                                    </li>
                                </ul>
                                <p v-if="searchReceiverQuery.length >= 3 && !receiverSuggestions.length && !searchingExisting" class="text-[9px] font-bold text-slate-400 mt-2 italic">
                                    * Si el receptor no aparece, es porque pertenece a otro representante.
                                </p>
                            </div>
                        </div>

                        <!-- 2.B: FICHA RESUMEN -->
                        <div v-if="['cliente', 'existente'].includes(orderForm.receiverType)" class="animate-fade-in">
                            <div v-if="activeReceiverDisplay" class="receiver-summary-card shadow-sm border border-red-100 rounded-[2.5rem] p-8 bg-white relative overflow-hidden group">
                                <div class="relative z-10 space-y-1">
                                    <p class="text-[10px] font-black text-red-400 uppercase tracking-[0.2em] mb-3">Información de Destino Seleccionada</p>
                                    <h4 class="text-2xl font-black text-black leading-none mb-3 uppercase tracking-tighter">{{ activeReceiverDisplay.nombre || activeReceiverDisplay.contacto || activeReceiverDisplay.name }}</h4>
                                    
                                    <div class="flex flex-wrap gap-x-8 gap-y-2">
                                        <p class="text-xs font-bold text-red-600 uppercase"><i class="fas fa-id-card mr-2 text-red-300"></i> RFC: <span class="text-black font-black">{{ activeReceiverDisplay.rfc }}</span></p>
                                        <p class="text-xs font-bold text-red-600 uppercase"><i class="fas fa-envelope mr-2 text-red-300"></i> {{ activeReceiverDisplay.correo || activeReceiverDisplay.email }}</p>
                                    </div>

                                    <div class="mt-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Régimen Fiscal</label>
                                        <p class="text-xs font-bold text-slate-800 uppercase flex items-center">
                                            <i class="fas fa-file-invoice text-red-700 mr-2"></i>
                                            {{ activeReceiverDisplay.regimen_fiscal || activeReceiverDisplay.receiver_regimen_fiscal || 'SIN RÉGIMEN' }}
                                        </p>
                                    </div>
                                    
                                   <p class="text-xs text-red-500 mt-4 italic font-medium leading-relaxed uppercase">
                                        <i class="fas fa-map-marker-alt mr-2 text-red-400"></i> 
                                        {{ activeReceiverDisplay.direccion }}
                                    </p>
                                </div>
                                <i class="fas fa-user-check absolute -right-6 -bottom-6 text-[10rem] text-red-50/50"></i>
                            </div>
                        </div>

                        <!-- 2.C: FORMULARIO MANUAL CON VALIDACIÓN INMEDIATA -->
                        <div v-if="orderForm.receiverType === 'nuevo'" class="animate-fade-in space-y-6 bg-white border border-red-100 p-8 rounded-[3rem] shadow-sm">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="form-group relative">
                                    <label class="label-style">RFC *</label>
                                    <input 
                                        v-model="orderForm.receiver.rfc" 
                                        @blur="validateUniqueness('rfc')" 
                                        type="text" 
                                        class="form-input font-mono uppercase font-black" 
                                        :class="fieldValidation.rfc.error ? 'border-red-600 bg-red-50 text-red-700' : 'text-slate-700'"
                                        placeholder="XXXXXXXXXXXXX" required minlength="12" maxlength="13"
                                    >
                                    <!-- MENSAJE DE ERROR DINÁMICO DESDE EL SERVIDOR -->
                                    <p v-if="fieldValidation.rfc.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                        <i class="fas fa-times-circle"></i> {{ fieldValidation.rfc.message || 'Dato no disponible' }}
                                    </p>
                                </div>
                                <div class="form-group relative">
                                    <label class="label-style">Destinatario *</label>
                                    <input 
                                        v-model="orderForm.receiver.persona_recibe" 
                                        @blur="validateUniqueness('persona_recibe')" 
                                        type="text" 
                                        class="form-input font-bold uppercase"
                                        :class="fieldValidation.persona_recibe.error ? 'border-red-600 bg-red-50 text-red-700' : ''"
                                        placeholder="NOMBRE COMPLETO" required minlength="5"
                                    >
                                    <p v-if="fieldValidation.persona_recibe.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                        <i class="fas fa-times-circle"></i> {{ fieldValidation.persona_recibe.message || 'Nombre duplicado' }}
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Régimen Fiscal *</label>
                                    <select v-model="orderForm.receiver.regimen_fiscal" required class="form-input font-bold text-xs text-red-700 uppercase">
                                        <option value="">SELECCIONAR...</option>
                                        <option value="601">601 - GENERAL MORALES</option>
                                        <option value="612">612 - PF ACT. EMPRESARIAL</option>
                                        <option value="626">626 - RESICO</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="label-style">Correo Electrónico *</label>
                                    <input 
                                        v-model="orderForm.receiver.correo" 
                                        @blur="validateUniqueness('correo')" 
                                        type="email" 
                                        class="form-input text-red-700 font-bold" 
                                        :class="fieldValidation.correo.error ? 'border-red-600 bg-red-50' : ''"
                                        placeholder="correo@ejemplo.com" required
                                    >
                                    <p v-if="fieldValidation.correo.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                        <i class="fas fa-times-circle"></i> {{ fieldValidation.correo.message || 'Correo ya registrado' }}
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Teléfono *</label>
                                    <input 
                                        v-model="orderForm.receiver.telefono" 
                                        @blur="validateUniqueness('telefono')" 
                                        type="tel" 
                                        class="form-input text-red-700 font-bold uppercase" 
                                        :class="fieldValidation.telefono.error ? 'border-red-600 bg-red-50' : ''"
                                        placeholder="10 DÍGITOS" required minlength="10" maxlength="10"
                                    >
                                    <p v-if="fieldValidation.telefono.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                        <i class="fas fa-times-circle"></i> {{ fieldValidation.telefono.message || 'Teléfono ya registrado' }}
                                    </p>
                                </div>
                            </div>

                            <div class="bg-red-50/20 p-8 rounded-[2.5rem] border border-red-100 space-y-6">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                                    <div class="form-group relative">
                                        <label class="label-mini">C.P. *</label>
                                        <input v-model="orderForm.receiver.cp" required type="text" class="form-input font-mono font-black uppercase" maxlength="5" @input="handleCPInput" placeholder="00000">
                                        <i v-if="searchingCP" class="fas fa-spinner fa-spin absolute right-3 top-10 text-red-600"></i>
                                    </div>
                                    <div class="form-group col-span-1"><label class="label-mini">Estado</label><input v-model="orderForm.receiver.estado" type="text" placeholder="ESTADO" class="form-input bg-white font-bold text-red-800 uppercase" readonly></div>
                                    <div class="form-group col-span-2"><label class="label-mini">Municipio</label><input v-model="orderForm.receiver.municipio" type="text" placeholder="MUNICIPIO" class="form-input bg-white font-bold text-red-800 uppercase" readonly></div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label class="label-mini">Colonia *</label>
                                        <select v-model="orderForm.receiver.colonia" required class="form-input font-bold text-red-700 uppercase" :disabled="!colonias.length">
                                            <option value="" disabled>{{ colonias.length ? 'SELECCIONE...' : 'INGRESE CP' }}</option>
                                            <option v-for="(col, idx) in colonias" :key="idx" :value="col">{{ col }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group"><label class="label-mini">Calle y Número *</label><input required minlength="10" v-model="orderForm.receiver.calle_num" type="text" class="form-input font-bold text-red-700 uppercase" placeholder="AV. JUÁREZ 123"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section shadow-premium border-t-4 border-t-black !overflow-visible">
                    <div class="form-group">
                        <label class="label-style">Comentarios Generales del Pedido:</label>
                        <textarea v-model="orderForm.comments" required minlength="10" class="form-input text-red-600 font-medium uppercase" rows="3" placeholder="NOTAS ADICIONALES..."></textarea>
                    </div>
                </div>

                <!-- 3. SELECCIÓN DE MATERIAL -->
                <div class="form-section !overflow-visible shadow-premium border-t-4 border-t-black" :class="{'border-red-500 ring-1 ring-red-100': errors.items}">
                    <div class="section-title text-black"><i class="fas fa-book-open text-red-700"></i> 3. Selección de Material</div>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end bg-red-50/20 p-6 rounded-[2.5rem] border border-red-100">
                        <div class="md:col-span-2"><label class="label-mini">Tipo</label><select v-model="currentOrderItem.tipo_material" class="form-input font-black uppercase text-[10px] text-red-700"><option value="promocion">PROMO</option><option value="venta">VENTA</option></select></div>
                        <div class="md:col-span-3 relative">
                            <label class="label-mini">Buscar Libro</label>
                            <div class="relative">
                                <input type="text" class="form-input pr-10 font-bold text-black uppercase" v-model="currentOrderItem.bookName" placeholder="TÍTULO..." @input="searchBooks" autocomplete="off">
                                <i v-if="searchingLibros" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                            </div>
                            <ul v-if="currentOrderItem.bookSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                <li v-for="book in currentOrderItem.bookSuggestions" :key="book.id" @click="selectBook(book)" class="p-3 border-b last:border-0 hover:bg-red-50 transition-colors cursor-pointer text-xs font-black uppercase text-black">{{ book.titulo }}</li>
                            </ul>
                        </div>
                        <div class="md:col-span-3"><label class="label-mini">Formato / Licencia</label><select class="form-input font-bold text-red-700 uppercase text-xs" v-model="currentOrderItem.sub_type" :disabled="!currentOrderItem.bookId"><option value="" disabled>SELECCIONAR...</option><option v-for="opt in availableSubTypes" :key="opt" :value="opt">{{ opt }}</option></select></div>
                        <div class="md:col-span-1"><label class="label-mini">Cant.</label><input type="number" min="1" class="form-input font-bold text-red-700" v-model.number="currentOrderItem.quantity"></div>
                        <div class="md:col-span-2"><label class="label-mini">P. Unitario ($)</label><input type="number" step="0.01" class="form-input font-black text-red-700 disabled:text-slate-400 disabled:bg-slate-100" v-model.number="currentOrderItem.price" :disabled="currentOrderItem.tipo_material === 'promocion'"></div>
                        <div class="md:col-span-1"><button type="button" @click="addItemToCart" class="btn-primary w-full py-4 rounded-2xl shadow-xl transition-all active:scale-95"><i class="fas fa-cart-plus mr-1"></i>Añadir</button></div>
                    </div>

                    <!-- TABLA DE CARRITO -->
                    <div class="mt-8 overflow-hidden rounded-[2.5rem] border border-red-100 bg-white shadow-premium">
                        <div class="table-responsive border-none">
                            <table class="min-width-full divide-y divide-red-200">
                                <thead class="bg-black text-white text-[9px] font-black uppercase tracking-widest">
                                    <tr><th class="px-6 py-5 text-left">Título</th><th class="px-6 py-5 text-center">Tipo</th><th class="px-6 py-5 text-center">Cant.</th><th class="px-6 py-5 text-right">Total</th><th class="px-6 py-5"></th></tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-red-50">
                                    <tr v-for="(item, index) in orderForm.orderItems" :key="item.id" class="hover:bg-red-50/50 transition-colors group">
                                        <td class="table-cell">
                                            <div class="font-black text-black text-[13px] uppercase leading-tight">{{ item.bookName }}</div>
                                            <div class="text-[9px] text-red-500 uppercase font-black tracking-widest mt-1">{{ item.sub_type }}</div>
                                        </td>
                                        <td class="table-cell text-center"><span :class="item.tipo_material === 'promocion' ? 'badge-material-promo' : 'badge-material-sale'">{{ item.tipo_material.toUpperCase() }}</span></td>
                                        <td class="table-cell text-center font-black text-red-800 text-lg">{{ item.quantity }}</td>
                                        <td class="table-cell text-right font-black text-red-700 text-sm">{{ formatCurrency(item.totalCost) }}</td>
                                        <td class="table-cell text-center"><button type="button" @click="orderForm.orderItems.splice(index, 1)" class="btn-delete-item"><i class="fas fa-trash-alt mr-1"></i> BORRAR</button></td>
                                    </tr>
                                    <tr v-if="!orderForm.orderItems.length"><td colspan="5" class="px-6 py-20 text-center italic text-slate-300 font-black text-[10px] uppercase tracking-widest">Sin ítems en la orden</td></tr>
                                </tbody>
                                <tfoot v-if="orderForm.orderItems.length" class="bg-red-50/30 border-t-2 border-red-100">
                                    <tr>
                                        <td colspan="2" class="px-8 py-8 text-right font-black text-[10px] uppercase text-red-800 tracking-[0.2em]">Inversión Total:</td>
                                        <td class="px-6 py-8 text-center font-black text-red-700 text-2xl border-x border-red-100/50">{{ totalUnits }}</td>
                                        <td class="px-6 py-8 text-right font-black text-3xl text-red-700 tracking-tighter leading-none">{{ formatCurrency(orderTotal) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-12 flex justify-end">
                    <button type="submit" class="btn-primary px-20 py-6 text-lg font-black tracking-widest shadow-2xl transition-all active:scale-95 disabled:opacity-50 disabled:grayscale" :disabled="loading || isFormBlockedByDuplicates">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-paper-plane mr-3'"></i> GENERAR PEDIDO
                    </button>
                </div>
            </form>
        </div>

        <!-- MODALES DE SISTEMA -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="systemModal.visible" class="modal-overlay-wrapper" @click.self="systemModal.type !== 'success' ? systemModal.visible = false : null">
                    
                    <!-- VISTA DE ÉXITO -->
                    <div v-if="systemModal.type === 'success'" class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="text-2xl font-black text-black mb-3 uppercase tracking-tighter">¡Pedido Registrado!</h2>
                        <p class="text-xs font-mono font-black text-white bg-red-700 py-2.5 px-6 rounded-xl inline-block mb-8 uppercase tracking-widest">FOLIO: {{ generatedOrderId }}</p>
                        <button type="button" @click="closeAndRedirect" class="btn-primary w-full py-5 bg-black border-none text-white font-black uppercase tracking-widest">Regresar al Historial</button>
                    </div>

                    <!-- VISTA DE ALERTA DE DUPLICADOS O ERRORES -->
                    <div v-else class="modal-content-success bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden border border-red-100 animate-scale-in">
                        <div class="bg-red-600 h-4 w-full"></div>
                        <div class="p-10 flex flex-col items-center">
                            
                            <!-- DISEÑO PERSONALIZADO PARA DUPLICADOS (REGLA SOLICITADA) -->
                            <div v-if="isFormBlockedByDuplicates" class="w-full">
                                <div class="bg-red-50 text-red-600 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6 shadow-sm border-4 border-white ring-2 ring-red-50">
                                    <i class="fas fa-exclamation-triangle text-3xl animate-pulse"></i>
                                </div>
                                <div class="text-danger bgcolor flex flex-col justify-content-center rounded-3 p-4 shadow-inner border border-danger mb-8">
                                    <h4 class="mb-2 font-black uppercase tracking-tighter text-red-700 text-sm">Atención: Registros duplicados</h4>
                                    <p class="mb-0 font-bold uppercase text-[10px] text-red-600 leading-relaxed text-center">
                                        {{ systemModal.errorList[0] || 'Este dato ya existe globalmente en el sistema.' }}
                                    </p>
                                </div>
                            </div>

                            <!-- DISEÑO ESTÁNDAR PARA OTROS ERRORES -->
                            <div v-else class="w-full flex flex-col items-center">
                                <div class="w-20 h-20 bg-red-50 text-red-600 rounded-full flex items-center justify-center mb-6 shadow-inner border-4 border-white">
                                    <i class="fas fa-ban text-3xl"></i>
                                </div>
                                <h2 class="text-2xl font-black text-black mb-2 uppercase tracking-tighter">{{ systemModal.title }}</h2>
                                <div class="w-full space-y-3 bg-red-50/30 p-6 rounded-[2rem] border border-red-100/50 mb-8 mt-4">
                                    <div v-for="(err, i) in systemModal.errorList" :key="i" class="text-[11px] font-black text-slate-700 uppercase leading-tight text-center">
                                        <i class="fas fa-exclamation-circle text-red-500 mr-1"></i> {{ err }}
                                    </div>
                                </div>
                            </div>

                            <button type="button" @click="systemModal.visible = false" class="btn-primary w-full py-5 bg-black border-none text-white font-black uppercase tracking-widest rounded-2xl transition-transform hover:scale-105">Revisar formulario</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const searchingLibros = ref(false);
const searchingClients = ref(false);
const searchingExisting = ref(false);
const searchingCP = ref(false);
const clientSuggestions = ref([]);
const receiverSuggestions = ref([]);
const estados = ref([]);
const colonias = ref([]);
const selectedCliente = ref(null); 
const selectedExistingReceiver = ref(null);
const searchReceiverQuery = ref('');
const generatedOrderId = ref('');

const errors = reactive({ clientId: false, items: false });
const validatingFields = reactive({ rfc: false, correo: false, telefono: false, persona_recibe: false });
const fieldValidation = reactive({ 
    rfc: { error: false, message: '' }, 
    correo: { error: false, message: '' }, 
    telefono: { error: false, message: '' }, 
    persona_recibe: { error: false, message: '' } 
});

const orderForm = reactive({
    prioridad: 'media', clientId: null, clientName: '', receiverType: '',
    receiver: { persona_recibe: '', rfc: '', regimen_fiscal: '', telefono: '', correo: '', cp: '', estado: '', municipio: '', colonia: '', calle_num: '' },
    logistics: { deliveryOption: '', paqueteria_nombre: '', comentarios_logistica: '' },
    comments: '', orderItems: [], 
});

const systemModal = reactive({ visible: false, type: 'success', title: '', errorList: [] });
const currentOrderItem = reactive({ bookId: null, bookName: '', tipo_material: 'venta', category: '', sub_type: '', quantity: 1, price: 0, bookSuggestions: [] });

// Lógica de duplicados: Solo aplica para el modo "Nuevo"
const isFormBlockedByDuplicates = computed(() => {
    if (orderForm.receiverType !== 'nuevo') return false;
    return fieldValidation.rfc.error || fieldValidation.correo.error || fieldValidation.telefono.error || fieldValidation.persona_recibe.error;
});

/**
 * REGLA SOLICITADA: Al detectar duplicado global, abrir modal automáticamente.
 */
watch(isFormBlockedByDuplicates, (val) => {
    if (val) {
        // Buscamos el primer mensaje de error disponible para el modal
        const activeError = fieldValidation.rfc.message || fieldValidation.correo.message || fieldValidation.telefono.message || fieldValidation.persona_recibe.message;
        systemModal.type = 'error';
        systemModal.title = 'Integridad de Datos';
        systemModal.errorList = [activeError || 'Los datos pertenecen a otro representante.'];
        systemModal.visible = true;
    }
});

const clearDuplicateErrors = () => {
    Object.keys(fieldValidation).forEach(k => {
        fieldValidation[k].error = false;
        fieldValidation[k].message = '';
    });
};

const activeReceiverDisplay = computed(() => {
    if (orderForm.receiverType === 'cliente') return selectedCliente.value;
    if (orderForm.receiverType === 'existente') return selectedExistingReceiver.value;
    return null;
});

const selectedClienteEstadoNombre = computed(() => {
    if (!selectedCliente.value || !selectedCliente.value.estado_id) return 'NO DEFINIDO';
    const match = estados.value.find(e => Number(e.id) === Number(selectedCliente.value.estado_id));
    return match ? match.estado.toUpperCase() : 'NO DEFINIDO';
});

// Limpieza al cambiar de modo
watch(() => orderForm.receiverType, (newVal) => {
    clearDuplicateErrors();
    if (newVal === 'nuevo') {
        orderForm.receiver = { persona_recibe: '', rfc: '', regimen_fiscal: '', telefono: '', correo: '', cp: '', estado: '', municipio: '', colonia: '', calle_num: '' };
        selectedExistingReceiver.value = null;
    }
});

const searchExistingReceivers = () => {
    selectedExistingReceiver.value = null; 
    if (searchReceiverQuery.value.length < 3) { receiverSuggestions.value = []; return; }
    searchingExisting.value = true;
    setTimeout(async () => {
        try {
            const res = await axios.get('/search/receptores', { params: { query: searchReceiverQuery.value } });
            receiverSuggestions.value = res.data;
        } catch (e) { console.error(e); } finally { searchingExisting.value = false; }
    }, 400);
};

const selectExistingReceiver = (rec) => {
    selectedExistingReceiver.value = rec;
    orderForm.receiver = { ...rec, persona_recibe: rec.nombre }; 
    receiverSuggestions.value = [];
    searchReceiverQuery.value = rec.nombre;
};

/**
 * VALIDACIÓN GLOBAL DE UNICIDAD:
 * Comprueba si los datos existen en toda la base de datos e informa al usuario.
 */
const validateUniqueness = async (field) => {
    let val = '';
    let queryParam = field; 
    if (field === 'persona_recibe') { val = orderForm.receiver.persona_recibe?.trim(); queryParam = 'nombre'; }
    else if (field === 'rfc') val = orderForm.receiver.rfc?.trim().toUpperCase();
    else if (field === 'correo') val = orderForm.receiver.correo?.trim().toLowerCase();
    else if (field === 'telefono') val = orderForm.receiver.telefono?.trim();

    // Requisito de longitud mínima para disparar la red
    if (!val || val.length < 5) { 
        if(fieldValidation[field]) {
            fieldValidation[field].error = false;
            fieldValidation[field].message = '';
        }
        return; 
    }

    validatingFields[field] = true;
    try {
        // Consultamos el endpoint unificado de unicidad
        const res = await axios.get('/search/receptores/check-rfc', { params: { [queryParam]: val } });
        
        // Si el servidor devuelve status 'conflict', hay un duplicado
        if (res.data.status === 'conflict') {
            fieldValidation[field].error = true;
            fieldValidation[field].message = res.data.message;
        } else {
            fieldValidation[field].error = false;
            fieldValidation[field].message = '';
        }
    } catch (e) { 
        fieldValidation[field].error = false; 
    } finally { 
        validatingFields[field] = false; 
    }
};

const availableSubTypes = computed(() => {
    if (!currentOrderItem.bookId) return [];
    const category = currentOrderItem.category?.toLowerCase() || '';
    if (currentOrderItem.tipo_material === 'promocion') return category === 'digital' ? ['Licencia Digital', 'Demo Digital'] : ['Físico (Muestra Promoción)'];
    return category === 'digital' ? ['Libro Digital'] : ['Pack (Físico + Digital)', 'Solo Físico'];
});

const handleCPInput = () => { if (orderForm.receiver.cp?.length === 5) fetchAddressByCP(orderForm.receiver.cp); };

const fetchAddressByCP = async (cp) => {
    searchingCP.value = true;
    try {
        const res = await axios.get(`/proxy/dipomex`, { params: { cp: cp } });
        if (res.data && res.data.codigo_postal) {
            orderForm.receiver.estado = res.data.codigo_postal.estado.toUpperCase();
            orderForm.receiver.municipio = res.data.codigo_postal.municipio.toUpperCase();
            colonias.value = res.data.codigo_postal.colonias.map(c => (c.colonia || c).toUpperCase());
        }
    } catch (e) { console.warn(e); } finally { searchingCP.value = false; }
};

const searchClients = () => {
    if (orderForm.clientName.length < 3) { clientSuggestions.value = []; return; }
    searchingClients.value = true;
    setTimeout(async () => {
        try {
            const res = await axios.get('/search/clientes', { params: { query: orderForm.clientName, include_prospectos: true } });
            clientSuggestions.value = res.data;
        } catch (e) { console.error(e); } finally { searchingClients.value = false; }
    }, 400);
};

const selectClient = (c) => {
    if (!c) return;
    orderForm.clientId = c.id; orderForm.clientName = c.name; selectedCliente.value = c; clientSuggestions.value = [];
    if (orderForm.receiverType === 'cliente') {
        orderForm.receiver.regimen_fiscal = c.regimen_fiscal ? c.regimen_fiscal.split(' ')[0] : '';
    }
};

const searchBooks = async () => {
    if (currentOrderItem.bookName.length < 3) { currentOrderItem.bookSuggestions = []; return; }
    searchingLibros.value = true;
    try {
        const res = await axios.get('/search/libros', { params: { query: currentOrderItem.bookName } });
        currentOrderItem.bookSuggestions = res.data.filter(b => {
            const bType = b.type?.toLowerCase() || '';
            return currentOrderItem.tipo_material === 'promocion' ? (bType === 'promocion' || bType === 'digital') : (bType === 'venta' || bType === 'digital');
        });
    } catch (e) { console.error(e); } finally { searchingLibros.value = false; }
};

const selectBook = (book) => {
    currentOrderItem.bookId = book.id; currentOrderItem.bookName = book.titulo; currentOrderItem.category = book.type; currentOrderItem.bookSuggestions = [];
    setTimeout(() => { if (availableSubTypes.value.length === 1) currentOrderItem.sub_type = availableSubTypes.value[0]; }, 100);
};

const isCurrentItemValid = computed(() => {
    return currentOrderItem.bookId !== null && currentOrderItem.sub_type !== '' && currentOrderItem.quantity >= 1;
});

const addItemToCart = () => {
    if (!isCurrentItemValid.value) return;
    orderForm.orderItems.push({
        id: Date.now(), bookId: currentOrderItem.bookId, bookName: currentOrderItem.bookName.toUpperCase(), tipo_material: currentOrderItem.tipo_material,
        sub_type: currentOrderItem.sub_type, quantity: currentOrderItem.quantity, price: currentOrderItem.price || 0, totalCost: (currentOrderItem.price || 0) * currentOrderItem.quantity
    });
    Object.assign(currentOrderItem, { bookId: null, bookName: '', sub_type: '', category: '', quantity: 1, price: 0, bookSuggestions: [] });
};

const totalUnits = computed(() => orderForm.orderItems.reduce((s, i) => s + i.quantity, 0));
const orderTotal = computed(() => orderForm.orderItems.reduce((s, i) => s + i.totalCost, 0));
const formatCurrency = (v) => Number(v).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

const submitOrder = async () => {
    if (isFormBlockedByDuplicates.value) return;

    const list = [];
    if (!orderForm.clientId) list.push("SELECCIONE CLIENTE.");
    if (orderForm.receiverType === 'existente' && !selectedExistingReceiver.value) list.push("SELECCIONE UN RECEPTOR DE LA LISTA DE BÚSQUEDA.");
    if (orderForm.orderItems.length === 0) list.push("LA CANASTA ESTÁ VACÍA.");

    if (list.length > 0) {
        systemModal.visible = true; systemModal.type = 'error'; systemModal.title = 'DATOS INCOMPLETOS'; systemModal.errorList = list;
        return;
    }

    loading.value = true;
    try {
        const finalData = JSON.parse(JSON.stringify(orderForm));
        
        if (orderForm.receiverType === 'cliente') {
            finalData.receiver = {
                persona_recibe: selectedCliente.value.contacto || selectedCliente.value.name,
                rfc: selectedCliente.value.rfc || '',
                regimen_fiscal: selectedCliente.value.regimen_fiscal ? selectedCliente.value.regimen_fiscal.split(' ')[0] : '', 
                telefono: selectedCliente.value.telefono || '',
                correo: selectedCliente.value.email || '',
                cp: selectedCliente.value.cp || '00000',
                estado: selectedClienteEstadoNombre.value || 'DESCONOCIDO',
                municipio: selectedCliente.value.municipio || 'DESCONOCIDO',
                colonia: selectedCliente.value.colonia || 'DESCONOCIDO',
                calle_num: selectedCliente.value.calle_num || selectedCliente.value.direccion || 'DOMICILIO CONOCIDO'
            };
        } else if (orderForm.receiverType === 'existente') {
            finalData.receptor_id = selectedExistingReceiver.value.id;
            finalData.receiver = {
                persona_recibe: selectedExistingReceiver.value.nombre,
                rfc: selectedExistingReceiver.value.rfc,
                regimen_fiscal: selectedExistingReceiver.value.receiver_regimen_fiscal || selectedExistingReceiver.value.regimen_fiscal || '',
                telefono: selectedExistingReceiver.value.telefono,
                correo: selectedExistingReceiver.value.correo,
                cp: '00000', estado: 'CARGADO', municipio: 'CARGADO', colonia: 'CARGADO',
                calle_num: selectedExistingReceiver.value.direccion
            };
        }

        const itemsPayload = orderForm.orderItems.map(i => ({ bookId: i.bookId, quantity: i.quantity, price: i.price, sub_type: i.sub_type, tipo_material: i.tipo_material }));
        const payload = { ...finalData, items: itemsPayload, commentary_delivery_option: orderForm.logistics.comentarios_logistica };

        const res = await axios.post('/pedidos', payload);
        generatedOrderId.value = res.data.order_id;
        systemModal.type = 'success'; systemModal.visible = true;
    } catch (e) {
        systemModal.type = 'error'; systemModal.title = 'ERROR AL PROCESAR';
        if (e.response?.status === 422 && e.response.data.errors) systemModal.errorList = Object.values(e.response.data.errors).flat();
        else systemModal.errorList = [e.response?.data?.message || "ERROR INESPERADO EN EL SERVIDOR."];
        systemModal.visible = true;
    } finally { loading.value = false; }
};

const closeAndRedirect = () => { systemModal.visible = false; router.push('/pedidos'); };
onMounted(async () => { try { const res = await axios.get('/estados'); estados.value = res.data; } catch (e) { console.error(e); } });
</script>

<style scoped>
.label-style { @apply text-xs font-black text-red-600 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.form-section { background: white; padding: 30px; border-radius: 40px; border: 1px solid #fee2e2; margin-bottom: 30px; }
.section-title { font-weight: 900; color: #000; margin-bottom: 25px; border-bottom: 2px solid #fee2e2; padding-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.85rem; }
.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #fee2e2; font-weight: 700; color: #334155; background: #fff; transition: 0.2s; font-size: 0.9rem; position: relative; z-index: 1; }
.form-input:focus { border-color: #000; outline: none; z-index: 2; }

.autocomplete-list { 
    position: absolute; 
    z-index: 10000; 
    width: 100%; 
    background: white !important; 
    border: 2px solid #fee2e2; 
    border-radius: 16px; 
    max-height: 300px; 
    overflow-y: auto; 
    padding: 8px; 
    box-shadow: 0 20px 40px rgba(0,0,0,0.15); 
    top: 100%; 
    left: 0; 
    margin-top: 6px; 
}

.shipping-card { @apply border-2 border-red-50 p-5 rounded-3xl flex flex-col items-center gap-3 cursor-pointer transition-all bg-white text-red-300; }
.shipping-card i { @apply text-3xl; }
.shipping-card span { @apply text-[10px] font-black uppercase tracking-widest text-center; }
.shipping-card.active { @apply border-black text-black shadow-xl scale-[1.02]; }
.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; transition: 0.2s; text-transform: uppercase; font-size: 0.8rem; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background: rgba(15,23,42,0.85); backdrop-filter: blur(8px); }
.modal-content-success { background: white; padding: 50px; border-radius: 50px; text-align: center; width: 90%; max-width: 450px; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #fee2e2; }
.success-icon-wrapper { width: 85px; height: 85px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 25px; border: 4px solid white; }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.table-cell { padding: 20px 24px; vertical-align: middle; color: #dc2626; font-weight: 700; }
.btn-delete-item { background: none; border: none; color: #fca5a5; font-size: 11px; font-weight: 900; cursor: pointer; }

input.uppercase { text-transform: uppercase; }

.badge-material-sale { @apply bg-black text-white px-3 py-1 rounded-full text-[9px] font-black; }
.badge-material-promo { @apply bg-red-600 text-white px-3 py-1 rounded-full text-[9px] font-black; }

.bgcolor { background: #e7f684; border-radius: 12px; padding: 16px; }
.text-danger { color: #dc2626; }
.border-danger { border-color: #dc2626; }
</style>