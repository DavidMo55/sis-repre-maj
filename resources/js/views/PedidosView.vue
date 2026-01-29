<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-black uppercase tracking-tighter">Ingreso de Pedidos</h1>
                    <p class="text-xs md:text-sm text-red-600 font-bold uppercase tracking-widest mt-1">Gestión logística avanzada vinculada a la ficha del cliente.</p>
                </div>
                <button @click="router.push('/pedidos')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>
            
            <form @submit.prevent="submitOrder" class="space-y-6">

                <!-- 1. INFORMACIÓN DEL CLIENTE -->
                <div class="form-section shadow-premium border-t-4 border-t-black" :class="{'border-red-500 ring-1 ring-red-100': errors.clientId}">
                    <div class="section-title text-black">
                        <i class="fas fa-user-circle"></i> 1. Información del Cliente
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-group relative">
                            <label class="label-style">Seleccionar Plantel / Distribuidor *</label>
                            <div class="relative">
                                <input 
                                    type="text" 
                                    class="form-input pl-10 font-bold lbb"  
                                    :class="{'border-red-600 bg-red-50': errors.clientId}"
                                    placeholder="Buscar por nombre..." 
                                    v-model="orderForm.clientName"
                                    @input="searchClients"
                                    required
                                >
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                                <i v-if="searchingClients" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                            </div>
                            <ul v-if="clientSuggestions.length" class="autocomplete-list shadow-2xl border border-red-50">
                                <li v-for="client in clientSuggestions" :key="client.id" @click="selectClient(client)" class="p-3 border-b last:border-0 hover:bg-red-50 transition-colors cursor-pointer">
                                    <div class="text-xs font-black text-black uppercase">{{ client.name }}</div>
                                    <div class="text-[9px] text-red-500 uppercase font-black tracking-widest mt-1">{{ client.tipo }}</div>
                                </li>
                            </ul>
                        </div>
                        <div class="form-group">
                            <label class="label-style">Prioridad de Atención:</label>
                            <select v-model="orderForm.prioridad" class="form-input font-bold text-red-700">
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. RECEPCIÓN Y LOGÍSTICA -->
                <div class="form-section shadow-premium border-t-4 border-t-black">
                    <div class="section-title text-black">
                        <i class="fas fa-truck"></i> 2. Recepción y Logística de Envío
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
                                    <input v-model="orderForm.logistics.paqueteria_nombre" type="text" required minlength="3" class="form-input border-red-200 bg-red-50/10 text-red-700 font-bold" placeholder="DHL, FedEx, etc.">
                                </div>
                                <div v-if="['recoleccion', 'entrega'].includes(orderForm.logistics.deliveryOption)">
                                    <label class="label-mini">Instrucciones / Referencias Logísticas:</label>
                                    <textarea v-model="orderForm.logistics.comentarios_logistica" class="form-input text-red-600 font-medium" rows="2" placeholder="Ej: Pasará el chofer el viernes..."></textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="border-red-100">

                        <div class="bg-red-50/20 p-5 rounded-3xl border border-red-100 flex flex-wrap gap-6 items-center">
                            <label class="text-[10px] font-black text-red-800 uppercase tracking-widest">Datos Fiscales y Dirección:</label>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="cliente" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600" :disabled="!orderForm.clientId">
                                    <span class="text-sm font-bold text-red-700 group-hover:text-black transition-colors">Último registro</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" value="nuevo" v-model="orderForm.receiverType" class="w-4 h-4 accent-red-600">
                                    <span class="text-sm font-bold text-red-700 group-hover:text-black transition-colors">Ingresar nuevos</span>
                                </label>
                            </div>
                        </div>

                        <!-- FICHA RESUMEN DEL CLIENTE -->
                        <div v-if="orderForm.receiverType === 'cliente'" class="animate-fade-in">
                            <div v-if="selectedCliente" class="receiver-summary-card shadow-sm border border-red-100 rounded-[2.5rem] p-8 bg-white relative overflow-hidden group">
                                <div class="relative z-10 space-y-1">
                                    <p class="text-[10px] font-black text-red-400 uppercase tracking-[0.2em] mb-3">Información de Envío Registrada</p>
                                    <h4 class="text-2xl font-black text-black leading-none mb-3 uppercase tracking-tighter">{{ selectedCliente.contacto || selectedCliente.name }}</h4>
                                    
                                    <div class="flex flex-wrap gap-x-8 gap-y-2">
                                        <p class="text-xs font-bold text-red-600 uppercase"><i class="fas fa-id-card mr-2 text-red-300"></i> RFC: <span :class="selectedCliente.rfc ? 'text-black font-black' : 'text-red-500 italic font-black'">{{ selectedCliente.rfc || 'FALTA RFC EN SISTEMA' }}</span></p>
                                        <p class="text-xs font-bold text-red-600 uppercase"><i class="fas fa-envelope mr-2 text-red-300"></i> {{ selectedCliente.email || 'SIN CORREO' }}</p>
                                    </div>
                                    
                                    <p class="text-xs text-red-500 mt-4 italic font-medium leading-relaxed">
                                        <i class="fas fa-map-marker-alt mr-2 text-red-400"></i> 
                                        {{ selectedCliente.direccion || 'Sin dirección registrada en la ficha maestra' }}
                                    </p>
                                </div>
                                <i class="fas fa-user-check absolute -right-6 -bottom-6 text-[10rem] text-red-50/50"></i>
                            </div>
                            <div v-else class="text-center py-16 border-2 border-dashed border-red-100 rounded-[2.5rem] bg-red-50/10">
                                <i class="fas fa-exclamation-triangle text-red-300 text-6xl mb-4"></i>
                                <p class="text-red-800 font-black uppercase text-xs tracking-[0.3em] italic">
                                    No se ha seleccionado ningún cliente.<br>
                                    Por favor, busque y seleccione un plantel o distribuidor.
                                </p>
                            </div>
                        </div>

                        <!-- FORMULARIO: DATOS NUEVOS (Con bloqueo de duplicados) -->
                        <div v-if="orderForm.receiverType === 'nuevo'" class="animate-fade-in space-y-6 bg-white border border-red-100 p-8 rounded-[3rem] shadow-sm">
                            
                            <div v-if="isFormBlockedByDuplicates" class="p-4 bg-red-600 text-white rounded-2xl flex items-center gap-4 animate-fade-in shadow-lg">
                                <i class="fas fa-ban text-2xl"></i>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-widest">Acción Prohibida: Datos Duplicados Detectados</p>
                                    <p class="text-[10px] font-bold opacity-80 mt-1 uppercase italic tracking-tighter">No se permite registrar información (RFC, Correo o Teléfono) que ya pertenezca a otro receptor.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="form-group relative">
                                    <label class="label-style">RFC *</label>
                                    <div class="relative">
                                        <input 
                                            v-model="orderForm.receiver.rfc" 
                                            @blur="validateUniqueness('rfc')"
                                            type="text" 
                                            class="form-input font-mono uppercase font-black text-red-700 border-red-100" 
                                            :class="{'border-red-600 bg-red-50 ring-2 ring-red-100': fieldValidation.rfc.error}" 
                                            placeholder="XXXXXXXXXXXXX"
                                            required minlength="13" maxlength="13"
                                        >
                                        <i v-if="validatingFields.rfc" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                                    </div>
                                    <p v-if="fieldValidation.rfc.error" class="text-[9px] text-red-600 font-black mt-1 uppercase tracking-tighter italic">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Este RFC ya existe en los registros.
                                    </p>
                                </div>

                                <div class="form-group">
                                    <label class="label-style">Destinatario *</label>
                                    <input v-model="orderForm.receiver.persona_recibe" type="text" required minlength="2" maxlength="255" class="form-input font-bold text-black" :class="{'border-red-600 bg-red-50': errors.persona_recibe}" placeholder="Nombre completo">
                                </div>

                                <div class="form-group">
                                    <label class="label-style">Régimen Fiscal</label>
                                    <select v-model="orderForm.receiver.regimen_fiscal" class="form-input font-bold text-xs text-red-700 uppercase">
                                        <option value="">Seleccionar...</option>
                                        <option value="601 - General de Ley Personas Morales">601 - General Morales</option>
                                        <option value="612 - Personas Físicas con Actividades Empresariales y Profesionales">612 - PF Act. Empresarial</option>
                                        <option value="626 - Régimen Simplificado de Confianza">626 - RESICO</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label class="label-style">Correo Electrónico *</label>
                                    <div class="relative">
                                        <input 
                                            v-model="orderForm.receiver.correo" 
                                            @blur="validateUniqueness('correo')"
                                            type="email" 
                                            class="form-input text-red-700 font-bold" 
                                            :class="{'border-red-600 bg-red-50 ring-2 ring-red-100': fieldValidation.correo.error}" 
                                            placeholder="correo@ejemplo.com"
                                            required
                                        >
                                        <i v-if="validatingFields.correo" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                                    </div>
                                    <p v-if="fieldValidation.correo.error" class="text-[9px] text-red-600 font-black mt-1 uppercase tracking-tighter italic">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Correo ya registrado. Ingrese otro.
                                    </p>
                                </div>

                                <div class="form-group">
                                    <label class="label-style">Teléfono *</label>
                                    <div class="relative">
                                        <input 
                                            v-model="orderForm.receiver.telefono" 
                                            @blur="validateUniqueness('telefono')"
                                            type="tel" 
                                            class="form-input text-red-700 font-bold" 
                                            :class="{'border-red-600 bg-red-50 ring-2 ring-red-100': fieldValidation.telefono.error}" 
                                            placeholder="Número de teléfono"
                                            required minlength="10" maxlength="12"
                                        >
                                        <i v-if="validatingFields.telefono" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                                    </div>
                                    <p v-if="fieldValidation.telefono.error" class="text-[9px] text-red-600 font-black mt-1 uppercase tracking-tighter italic">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Este Teléfono ya está en uso.
                                    </p>
                                </div>
                            </div>

                            <!-- GEOLOCALIZACIÓN -->
                            <div class="bg-red-50/20 p-8 rounded-[2.5rem] border border-red-100 space-y-6">
                                <p class="text-[10px] font-black text-red-800 uppercase tracking-[0.2em] mb-2 border-b border-red-200 pb-3">Geolocalización por Código Postal</p>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                    <div class="form-group relative">
                                        <label class="label-mini">C.P. *</label>
                                        <input v-model="orderForm.receiver.cp" required type="text" class="form-input font-mono font-black text-red-700 shadow-sm" :class="{'border-red-600 bg-red-50': errors.cp}" maxlength="5" @input="handleCPInput" placeholder="00000">
                                        <i v-if="searchingCP" class="fas fa-spinner fa-spin absolute right-3 top-10 text-red-600"></i>
                                    </div>
                                    <div class="form-group"><label class="label-mini">Estado</label><input v-model="orderForm.receiver.estado" type="text" placeholder="Ingrese CP" class="form-input bg-white font-bold text-red-700" readonly></div>
                                    <div class="form-group md:col-span-2"><label class="label-mini">Municipio / Alcaldía</label><input v-model="orderForm.receiver.municipio" type="text" placeholder="Ingrese CP" class="form-input bg-white font-bold text-red-700" readonly></div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label class="label-mini">Colonia / Asentamiento *</label>
                                        <select v-model="orderForm.receiver.colonia" required class="form-input font-bold text-red-700 uppercase" :class="{'border-red-600 bg-red-50': errors.colonia}" :disabled="!colonias.length">
                                            <option value="" disabled>{{ colonias.length ? 'Seleccione colonia...' : 'Ingrese CP' }}</option>
                                            <option v-for="(col, idx) in colonias" :key="idx" :value="col.colonia || col">
                                                {{ col.colonia || col }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="form-group"><label  class="label-mini">Calle y Número *</label><input required minlength="10" v-model="orderForm.receiver.calle_num" type="text" class="form-input font-bold text-red-700" :class="{'border-red-600 bg-red-50': errors.calle_num}" placeholder="Ej: Av. Juárez 123"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="label-style">Comentarios Generales del Pedido:</label>
                            <textarea v-model="orderForm.comments" required minlength="15" class="form-input text-red-600 font-medium" rows="3" placeholder="Notas adicionales para el equipo..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- 3. SELECCIÓN DE MATERIAL -->
                <div class="form-section !overflow-visible shadow-premium border-t-4 border-t-black" :class="{'border-red-500 ring-1 ring-red-100': errors.items}">
                    <div class="section-title text-black">
                        <i class="fas fa-book-open"></i> 3. Selección de Material
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end bg-red-50/20 p-6 rounded-[2.5rem] border border-red-100">
                        <div class="md:col-span-2">
                            <label class="label-mini">Rubro</label>
                            <select v-model="currentOrderItem.tipo_material" class="form-input font-black uppercase text-[10px] text-red-700">
                                <option value="promocion">PROMO</option>
                                <option value="venta">VENTA</option>
                            </select>
                        </div>

                        <div class="md:col-span-3 relative">
                            <label class="label-mini">Buscar Libro</label>
                            <div class="relative">
                                <input type="text" class="form-input pr-10 font-bold text-black" v-model="currentOrderItem.bookName" placeholder="Título..." @input="searchBooks">
                                <i v-if="searchingLibros" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-600"></i>
                            </div>
                            <ul v-if="currentOrderItem.bookSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                <li v-for="book in currentOrderItem.bookSuggestions" :key="book.id" @click="selectBook(book)" class="p-3 border-b last:border-0 hover:bg-red-50 cursor-pointer transition-colors">
                                    <div class="flex justify-between items-center overflow-hidden gap-2">
                                        <span class="text-xs font-black text-black truncate uppercase leading-tight">{{ book.titulo }}</span>
                                        <span class="text-[7px] bg-red-800 text-white px-2 py-0.5 rounded font-black uppercase shrink-0">{{ book.type }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="label-mini">Formato / Licencia</label>
                            <select class="form-input font-bold text-red-700 uppercase text-xs" v-model="currentOrderItem.sub_type" :disabled="!currentOrderItem.bookId">
                                <option value="" disabled>Seleccionar...</option>
                                <option v-for="opt in availableSubTypes" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-1">
                            <label class="label-mini">Cant.</label>
                            <input type="number" min="1" class="form-input font-bold text-red-700 text-left" v-model.number="currentOrderItem.quantity">
                        </div>

                        <div class="md:col-span-2">
                            <label class="label-mini">P. Unitario ($)</label>
                            <input 
                                type="number" 
                                step="0.01" 
                                class="form-input font-black text-red-700 disabled:text-slate-400 disabled:bg-slate-100" 
                                v-model.number="currentOrderItem.price" 
                                :disabled="currentOrderItem.tipo_material === 'promocion'"
                            >
                        </div>

                        <div class="md:col-span-1">
                            <button type="button" @click="addItemToCart" class="btn-primary w-full py-4 rounded-2xl shadow-xl transition-all active:scale-95">
                                <i class="fas fa-cart-plus"></i> Añadir
                            </button>
                        </div>
                    </div>

                    <!-- TABLA DE CARRITO -->
                    <div class="mt-8 overflow-hidden rounded-[2.5rem] border border-red-100 bg-white shadow-premium">
                        <div class="table-responsive border-none">
                            <table class="min-width-full divide-y divide-red-200">
                                <thead class="bg-black text-white">
                                    <tr>
                                        <th class="table-header-dark text-left text-white">Material / Título</th>
                                        <th class="table-header-dark text-center text-white">Tipo</th>
                                        <th class="table-header-dark text-center text-white">Cant.</th>
                                        <th class="table-header-dark text-right text-white">Total</th>
                                        <th class="table-header-dark"></th>
                                    </tr>
                                </thead>

                                <tbody class="bg-white divide-y divide-red-50">
                                    <tr v-for="(item, index) in orderForm.orderItems" :key="item.id" class="hover:bg-red-50/50 transition-colors group">
                                        <td class="table-cell">
                                            <div class="font-black text-black text-[13px] uppercase leading-tight">
                                                {{ item.bookName }}
                                            </div>
                                            <div class="text-[9px] text-red-500 uppercase font-black tracking-widest mt-1">
                                                {{ item.sub_type }}
                                            </div>
                                        </td>

                                        <td class="table-cell text-center">
                                            <span :class="item.tipo_material === 'promocion' ? 'badge-material-promo' : 'badge-material-sale'">
                                                {{ item.tipo_material.toUpperCase() }}
                                            </span>
                                        </td>

                                        <td class="table-cell text-center font-black text-red-800 text-lg">
                                            {{ item.quantity }}
                                        </td>

                                        <td class="table-cell text-right font-black text-red-700 text-sm">
                                            {{ formatCurrency(item.totalCost) }}
                                        </td>

                                        <td class="table-cell text-center">
                                            <button type="button" 
                                                    @click="orderForm.orderItems.splice(index, 1)" 
                                                    class="btn-delete-item group-hover:text-black">
                                                <i class="fas fa-trash-alt mr-1"></i> Cancelar
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="!orderForm.orderItems.length">
                                        <td colspan="5" class="px-6 py-20 text-center">
                                            <div class="flex flex-col items-center opacity-30">
                                                <i class="fas fa-shopping-basket text-red-200 text-6xl mb-4"></i>
                                                <p class="text-red-800 font-black uppercase text-xs tracking-[0.3em] italic">
                                                    Sin ítems en la orden
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>

                                <tfoot v-if="orderForm.orderItems.length" class="bg-red-50/30 border-t-2 border-red-100">
                                    <tr>
                                        <td colspan="2" class="px-8 py-8 text-right font-black text-[10px] uppercase text-red-800 tracking-[0.2em]">
                                            Inversión Total del Pedido:
                                        </td>
                                        <td class="px-6 py-8 text-center font-black text-red-700 text-2xl border-x border-red-100/50">
                                            <span class="text-[9px] block text-red-400 font-bold mb-1 uppercase tracking-widest">Unidades</span>
                                            {{ totalUnits }}
                                        </td>
                                        <td class="px-6 py-8 text-right font-black text-3xl text-red-700 tracking-tighter">
                                            <span class="text-[9px] block text-red-400 font-bold mb-1 uppercase tracking-widest">Monto Final</span>
                                            {{ formatCurrency(orderTotal) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-12 flex justify-end">
                    <button type="submit" class="btn-primary px-20 py-6 text-lg font-black tracking-widest shadow-2xl transition-all active:scale-95" :disabled="loading || isFormBlockedByDuplicates">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-paper-plane mr-3'"></i> 
                        Generar Pedido
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL INTEGRAL (ÉXITO / ERROR / VALIDACIÓN) -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="systemModal.visible" class="modal-overlay-wrapper" @click.self="systemModal.type !== 'success' ? systemModal.visible = false : null">
                    
                    <!-- VISTA DE ÉXITO -->
                    <div v-if="systemModal.type === 'success'" class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="text-2xl font-black text-black mb-3 uppercase tracking-tighter">¡Pedido Registrado!</h2>
                        <p class="text-sm text-red-600 mb-4 font-bold">La orden ha sido enviada al área de revisión.</p>
                        <p class="text-xs font-mono font-black text-white bg-red-700 py-2.5 px-6 rounded-xl inline-block mb-8 uppercase tracking-widest">FOLIO: {{ generatedOrderId }}</p>
                        <button @click="closeAndRedirect" class="btn-primary w-full py-5 bg-black border-none shadow-none text-white font-black uppercase tracking-widest">Regresar al Historial</button>
                    </div>

                    <!-- VISTA DE ERROR CONCRETO -->
                    <div v-else class="modal-content-success bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden border border-red-100 animate-scale-in">
                        <div class="bg-red-600 h-4 w-full"></div>
                        
                        <div class="p-10 flex flex-col items-center">
                            <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-6 shadow-lg border border-red-100">
                                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                            </div>
                            
                            <h2 class="text-2xl font-black text-black mb-2 uppercase tracking-tighter text-center">
                                {{ systemModal.title }}
                            </h2>
                            
                            <p class="text-[10px] text-red-600 font-black uppercase tracking-widest mb-6 text-center">
                                No se pudo completar la acción:
                            </p>
                            
                            <div class="w-full space-y-3 bg-red-50/30 p-6 rounded-[2rem] border border-red-100/50 mb-8">
                                <div v-for="(err, i) in systemModal.errorList" :key="i" class="flex items-start gap-3">
                                    <div class="w-1.5 h-1.5 rounded-full bg-red-600 mt-1.5 shrink-0"></div>
                                    <span class="text-[11px] font-black text-slate-700 uppercase leading-tight text-left">
                                        {{ err }}
                                    </span>
                                </div>
                            </div>
                        
                            <button @click="systemModal.visible = false" 
                                    class="btn-primary w-full max-w-[280px] py-5 bg-black border-none text-white font-black uppercase tracking-widest rounded-2xl transition-transform hover:scale-105">
                                Entendido, completar ahora
                            </button>
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
const searchingCP = ref(false);
const generatedOrderId = ref('');
const clientSuggestions = ref([]);
const estados = ref([]);
const colonias = ref([]);
const selectedCliente = ref(null); 

// Estado de errores para señalización visual (Bordes rojos)
const errors = reactive({
    clientId: false, persona_recibe: false, rfc: false,
    correo: false, telefono: false, cp: false,
    colonia: false, calle_num: false, items: false
});

// Validación de Unicidad
const validatingFields = reactive({ rfc: false, correo: false, telefono: false });
const fieldValidation = reactive({
    rfc: { error: false, message: '' },
    correo: { error: false, message: '' },
    telefono: { error: false, message: '' }
});

const isFormBlockedByDuplicates = computed(() => {
    if (orderForm.receiverType === 'cliente') return false;
    return fieldValidation.rfc.error || fieldValidation.correo.error || fieldValidation.telefono.error;
});

// Modal de Sistema
const systemModal = reactive({ visible: false, type: 'success', title: '', errorList: [] });

const orderForm = reactive({
    prioridad: 'media',
    clientId: null,
    clientName: '',
    receiverType: 'cliente',
    receiver: { 
        persona_recibe: '', rfc: '', regimen_fiscal: '', telefono: '', correo: '', cp: '', estado: '', municipio: '', colonia: '', calle_num: '' 
    },
    logistics: { deliveryOption: 'paqueteria', paqueteria_nombre: '', comentarios_logistica: '' },
    comments: '',
    orderItems: [], 
});

const currentOrderItem = reactive({
    bookId: null, bookName: '', tipo_material: 'venta', 
    category: '', sub_type: '', quantity: 1, price: 0,
    bookSuggestions: [],
});

watch(() => currentOrderItem.tipo_material, (val) => {
    currentOrderItem.bookName = '';
    currentOrderItem.bookId = null;
    currentOrderItem.bookSuggestions = [];
    currentOrderItem.sub_type = '';
    if (val === 'promocion') currentOrderItem.price = 0;
});

/**
 * REGLA: Verificación de Unicidad campo por campo
 */
const validateUniqueness = async (field) => {
    let val = '';
    if (field === 'rfc') val = orderForm.receiver.rfc?.trim().toUpperCase();
    if (field === 'correo') val = orderForm.receiver.correo?.trim().toLowerCase();
    if (field === 'telefono') val = orderForm.receiver.telefono?.trim();

    if (!val || val.length < 5) {
        fieldValidation[field].error = false;
        return;
    }

    validatingFields[field] = true;
    fieldValidation[field].error = false;

    try {
        const res = await axios.get('/search/receptores/rfc', { params: { [field]: val } });
        if (res.data && res.data.id) {
            fieldValidation[field].error = true;
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
    const isDigital = category === 'digital';
    const isPromo = currentOrderItem.tipo_material === 'promocion';
    
    if (isPromo) {
        return isDigital 
            ? ['Licencia Digital', 'Demo Digital'] 
            : ['Físico (Muestra Promoción)'];
    } else {
        return isDigital 
            ? ['Libro Digital'] 
            : ['Pack (Físico + Digital)', 'Solo Físico'];
    }
});

const handleCPInput = () => {
    const cp = orderForm.receiver.cp;
    if (cp && cp.length === 5) fetchAddressByCP(cp);
    else resetAddress();
};

const fetchAddressByCP = async (cp, preserveColonia = false) => {
    searchingCP.value = true;
    if (!preserveColonia) orderForm.receiver.colonia = '';
    colonias.value = [];
    
    try {
        const res = await axios.get(`/proxy/dipomex`, { params: { cp: cp } });
        if (res.data && !res.data.error && res.data.codigo_postal) {
            const data = res.data.codigo_postal;
            orderForm.receiver.estado = data.estado || '';
            orderForm.receiver.municipio = data.municipio || '';
            if (data.colonias && Array.isArray(data.colonias)) {
                colonias.value = data.colonias.map(c => c.colonia || c);
                if (!preserveColonia && colonias.value.length === 1) orderForm.receiver.colonia = colonias.value[0];
            }
        }
    } catch (e) { console.warn("Fallo Dipomex."); } finally { searchingCP.value = false; }
};

const resetAddress = () => { orderForm.receiver.estado = ''; orderForm.receiver.municipio = ''; orderForm.receiver.colonia = ''; colonias.value = []; };

const searchClients = () => {
    let clientTimeout = null;
    clearTimeout(clientTimeout);
    if (orderForm.clientName.length < 3) { clientSuggestions.value = []; return; }
    searchingClients.value = true;
    clientTimeout = setTimeout(async () => {
        try {
            const res = await axios.get('/search/clientes', { params: { query: orderForm.clientName, include_prospectos: true } });
            clientSuggestions.value = res.data;
        } catch (e) { console.error(e); } finally { searchingClients.value = false; }
    }, 400);
};

const selectClient = (c) => {
    if (!c) return;
    errors.clientId = false;
    orderForm.clientId = c.id;
    orderForm.clientName = c.name;
    selectedCliente.value = c; 
    clientSuggestions.value = [];
};

const searchBooks = async () => {
    if (currentOrderItem.bookName.length < 3) { currentOrderItem.bookSuggestions = []; return; }
    searchingLibros.value = true;
    try {
        const res = await axios.get('/search/libros', { params: { query: currentOrderItem.bookName } });
        const isPromo = currentOrderItem.tipo_material === 'promocion';
        currentOrderItem.bookSuggestions = res.data.filter(b => {
            const bType = b.type?.toLowerCase() || '';
            return isPromo ? (bType === 'promocion' || bType === 'digital') : (bType === 'venta' || bType === 'digital');
        });
    } catch (e) { console.error(e); } finally { searchingLibros.value = false; }
};

const selectBook = (book) => {
    currentOrderItem.bookId = book.id;
    currentOrderItem.bookName = book.titulo;
    currentOrderItem.category = book.type; 
    currentOrderItem.bookSuggestions = [];
    currentOrderItem.sub_type = ''; 
    
    setTimeout(() => { 
        if (availableSubTypes.value.length === 1) {
            currentOrderItem.sub_type = availableSubTypes.value[0];
        }
    }, 100);
};

const isCurrentItemValid = computed(() => {
    return currentOrderItem.bookId !== null && 
           currentOrderItem.sub_type !== '' && 
           currentOrderItem.quantity >= 1;
});

const addItemToCart = () => {
    if (!isCurrentItemValid.value) {
        systemModal.title = 'Material Incompleto';
        systemModal.type = 'error';
        systemModal.errorList = [
            !currentOrderItem.bookId ? "Debe seleccionar un libro de la lista de resultados." : null,
            currentOrderItem.sub_type === '' ? "Debe elegir el formato o tipo de licencia." : null,
            currentOrderItem.quantity < 1 ? "La cantidad debe ser al menos 1 unidad." : null
        ].filter(msg => msg !== null);
        systemModal.visible = true;
        return;
    }

    errors.items = false;
    orderForm.orderItems.push({
        id: Date.now(),
        bookId: currentOrderItem.bookId,
        bookName: currentOrderItem.bookName,
        tipo_material: currentOrderItem.tipo_material,
        sub_type: currentOrderItem.sub_type, 
        quantity: currentOrderItem.quantity,
        price: currentOrderItem.price || 0,
        totalCost: (currentOrderItem.price || 0) * currentOrderItem.quantity
    });

    const lastType = currentOrderItem.tipo_material;
    Object.assign(currentOrderItem, { bookId: null, bookName: '', sub_type: '', category: '', quantity: 1, price: 0, bookSuggestions: [], tipo_material: lastType });
};

const orderTotal = computed(() => orderForm.orderItems.reduce((s, i) => s + i.totalCost, 0));
const totalUnits = computed(() => orderForm.orderItems.reduce((s, i) => s + i.quantity, 0));
const formatCurrency = (v) => v.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

const validateForm = () => {
    Object.keys(errors).forEach(key => errors[key] = false);
    const list = [];
    if (!orderForm.clientId) { errors.clientId = true; list.push("Sección 1: Seleccione Cliente."); }
    
    if (orderForm.receiverType === 'nuevo') {
        if (!orderForm.receiver.persona_recibe) { errors.persona_recibe = true; list.push("Sección 2: Destinatario obligatorio."); }
        if (!orderForm.receiver.rfc || orderForm.receiver.rfc.length < 12) { errors.rfc = true; list.push("Sección 2: RFC válido obligatorio."); }
        if (!orderForm.receiver.cp || orderForm.receiver.cp.length !== 5) { errors.cp = true; list.push("Sección 2: C.P. obligatorio."); }
        if (!orderForm.receiver.colonia) { errors.colonia = true; list.push("Sección 2: Colonia obligatoria."); }
        if (!orderForm.receiver.calle_num) { errors.calle_num = true; list.push("Sección 2: Calle y número."); }
        
        if (isFormBlockedByDuplicates.value) {
            list.push("Existen datos duplicados (RFC, Correo o Teléfono) que ya pertenecen a un registro guardado.");
        }
    } else {
        if (!selectedCliente.value?.rfc) list.push("La ficha del cliente no tiene RFC registrado.");
        if (!selectedCliente.value?.direccion) list.push("La ficha del cliente no tiene dirección registrada.");
    }

    if (orderForm.orderItems.length === 0) { errors.items = true; list.push("Sección 3: No hay libros en la canasta."); }
    
    if (list.length > 0) {
        systemModal.type = 'error';
        systemModal.title = 'Datos Incompletos / Duplicados';
        systemModal.errorList = list;
        systemModal.visible = true;
        return false;
    }
    return true;
};

const submitOrder = async () => {
    if (orderForm.receiverType === 'nuevo') {
        loading.value = true; 
        await Promise.all([
            validateUniqueness('rfc'),
            validateUniqueness('correo'),
            validateUniqueness('telefono')
        ]);
    }

    if (!validateForm()) {
        loading.value = false;
        return;
    }

    loading.value = true;
    try {
        const finalData = JSON.parse(JSON.stringify(orderForm));
        
        if (orderForm.receiverType === 'cliente') {
            const estadoObj = estados.value.find(e => e.id === selectedCliente.value.estado_id);
            const nombreEstado = estadoObj ? estadoObj.estado : 'No definido';

            finalData.receiver = {
                persona_recibe: selectedCliente.value.contacto || selectedCliente.value.name,
                rfc: selectedCliente.value.rfc,
                regimen_fiscal: selectedCliente.value.regimen_fiscal,
                telefono: selectedCliente.value.telefono,
                correo: selectedCliente.value.email,
                cp: selectedCliente.value.cp,
                estado: nombreEstado,
                municipio: selectedCliente.value.municipio,
                colonia: selectedCliente.value.colonia,
                calle_num: selectedCliente.value.calle_num || selectedCliente.value.direccion
            };
        }

        const itemsPayload = orderForm.orderItems.map(i => ({
            bookId: i.bookId,
            quantity: i.quantity,
            price: i.price,
            sub_type: i.sub_type, 
            tipo_material: i.tipo_material
        }));

        const res = await axios.post('/pedidos', { ...finalData, items: itemsPayload });
        generatedOrderId.value = res.data.order_id;
        systemModal.type = 'success';
        systemModal.visible = true;
    } catch (e) { 
        console.error(e);
        systemModal.type = 'error';
        systemModal.title = 'Fallo de Servidor';
        systemModal.errorList = [e.response?.data?.message || "Ocurrió un error inesperado al intentar guardar el pedido profesional."];
        systemModal.visible = true;
    } finally { loading.value = false; }
};

const closeAndRedirect = () => { systemModal.visible = false; router.push('/pedidos'); };

onMounted(async () => {
    try {
        const res = await axios.get('/estados');
        estados.value = res.data;
    } catch (e) { console.error(e); }
});
</script>

<style scoped>
.label-style { @apply text-xs font-black text-red-600 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-red-500 mb-1 block; }

.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.form-section { background: white; padding: 30px; border-radius: 40px; border: 1px solid #fee2e2; margin-bottom: 30px; transition: all 0.2s ease; }
.section-title { font-weight: 900; color: #000000; margin-bottom: 25px; border-bottom: 2px solid #fee2e2; padding-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 2px; }

.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #fee2e2; font-weight: 700; color: #000000; background: #fff; transition: all 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #000000; outline: none; box-shadow: 0 0 0 4px rgba(0,0,0,0.02); }

.autocomplete-list { position: absolute; z-index: 2000; width: 100%; background: white; border: 1px solid #fee2e2; border-radius: 16px; max-height: 300px; overflow-y: auto; list-style: none; padding: 8px; margin-top: 8px; box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
.autocomplete-list li:hover { background: #fdf2f2; }

.shipping-card { @apply border-2 border-red-50 p-5 rounded-3xl flex flex-col items-center gap-3 cursor-pointer transition-all hover:border-red-200 text-red-300 bg-white; }
.shipping-card i { @apply text-3xl; }
.shipping-card span { @apply text-[10px] font-black uppercase tracking-widest text-center; }
.shipping-card.active { @apply border-black bg-white text-black shadow-xl scale-[1.02]; }

.btn-primary-action { background: #000000; color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15); transition: all 0.2s; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.1em; display: flex; align-items: center; justify-content: center; }
.btn-primary-action:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25); }

.btn-primary-black { background: #000000; color: white; border-radius: 16px; font-weight: 900; cursor: pointer; border: none; transition: all 0.2s; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; }
.btn-primary-black:hover:not(:disabled) { transform: scale(1.02); background: #1e293b; }

.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1.5rem; background-color: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); overflow: hidden; }
.modal-content-success { background: white; padding: 50px; border-radius: 50px; text-align: center; width: 90%; max-width: 400px; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #fee2e2; }
.success-icon-wrapper { width: 85px; height: 85px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 30px; border: 5px solid white; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.modal-pop-enter-active, .modal-pop-leave-active { transition: all 0.3s ease; }
.modal-pop-enter-from, .modal-pop-leave-to { opacity: 0; }

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23dc2626' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 1rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 3rem; appearance: none; }

.table-responsive { width: 100%; overflow-x: auto; background: white; border-radius: 2.5rem; }
table { width: 100%; border-collapse: collapse; }
.lbb { color: #000000; }

.table-header-dark { padding: 20px 24px; font-size: 0.7rem; font-weight: 900; color: #ffffff; text-transform: uppercase; letter-spacing: 0.25em; }
.table-cell { padding: 20px 24px; vertical-align: middle; color: #dc2626; font-weight: 700; }

.badge-material-promo { background-color: #f5f3ff; color: #7e22ce; border: 1px solid #ddd6fe; padding: 6px 14px; border-radius: 9999px; font-size: 9px; font-weight: 900; }
.badge-material-sale { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 6px 14px; border-radius: 9999px; font-size: 9px; font-weight: 900; }

.btn-delete-item { background: none; border: none; color: #fca5a5; font-size: 11px; font-weight: 900; text-transform: uppercase; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; }
.btn-delete-item:hover { color: #000000; transform: translateX(3px); }

.btn-secondary-custom { @apply bg-white border-2 border-slate-200 py-2.5 px-6 rounded-xl text-sm font-black transition-all hover:bg-slate-50 text-black; }

tfoot { border-top: 4px solid #000000; }
.table-shadow-lg { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }

/* Utilitarios */
.text-right { text-align: right; }
.text-center { text-align: center; }
.text-left { text-align: left; }

input:disabled {
    background-color: #f1f5f9 !important; /* slate-100 */
    color: #94a3b8 !important;           /* slate-400 */
    cursor: not-allowed;
    border-color: #e2e8f0;
}

.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

</style>