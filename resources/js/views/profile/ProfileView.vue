<template>
  <div class="profile-page-wrapper min-h-screen bg-slate-50 animate-fade-in pb-20">
    <!-- Header Especial de Perfil -->
    <header class="profile-header bg-white shadow-sm border-b border-slate-100 sticky top-0 z-50">
      <div class="header-container max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-black text-slate-800 tracking-tight flex items-center gap-3">
            <i class="fas fa-id-card text-red-700"></i> Mi Perfil Profesional
          </h1>
        </div>
       
      </div>
    </header>

    <!-- Loader Inicial -->
    <div v-if="loadingData" class="flex flex-col items-center justify-center py-40 animate-pulse">
        <i class="fas fa-circle-notch fa-spin text-5xl text-red-700 mb-4"></i>
        <p class="text-slate-400 font-bold uppercase tracking-widest text-xs italic">Sincronizando perfil...</p>
    </div>

    <div v-else class="max-w-7xl mx-auto px-6 flex flex-col lg:flex-row gap-8 py-10">
      <!-- Sidebar de Navegación Interna -->
      <aside class="w-full lg:w-80 shrink-0">
        <div class="inner-nav-card shadow-premium sticky top-28">
          
          <nav class="p-4 space-y-2">
            <button @click="activeSection = 'personal'" :class="['inner-nav-item', { 'active': activeSection === 'personal' }]">
              <i class="fas fa-user-edit"></i><span>Datos Personales</span>
            </button>
            <button @click="activeSection = 'tools'" :class="['inner-nav-item', { 'active': activeSection === 'tools' }]">
              <i class="fas fa-tools"></i><span>Herramientas de Trabajo</span>
            </button>
            <button @click="activeSection = 'security'" :class="['inner-nav-item', { 'active': activeSection === 'security' }]">
              <i class="fas fa-shield-alt"></i><span>Seguridad</span>
            </button>
            <button v-if="user.position !== 'Delegado Autorizado'" @click="activeSection = 'delegates'" :class="['inner-nav-item', { 'active': activeSection === 'delegates' }]">
              <i class="fas fa-users-cog"></i><span>Gestionar Delegados</span>
            </button>
          </nav>

          <div class="p-6 bg-slate-50 rounded-b-3xl text-center border-t border-slate-100">
          </div>
        </div>
      </aside>

      <!-- ÁREA PRINCIPAL -->
      <main class="flex-1 min-w-0">
        
        <!-- SECCIÓN: DATOS PERSONALES -->
        <section v-if="activeSection === 'personal'" class="form-card shadow-premium animate-slide-up">
          <div class="section-title-box mb-8">
            <h2 class="form-title">Identidad y Ubicación</h2>
            <p class="text-xs text-slate-400 mt-1 uppercase font-black tracking-widest">Información oficial de contacto</p>
          </div>

          <form @submit.prevent="confirmUpdateProfile">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
              <div class="form-group col-span-full md:col-span-1">
                <label>Nombre Completo (Real) *</label>
                <input v-model="user.full_name" type="text" class="form-input font-bold" placeholder="Nombre completo" required>
                <p class="text-[10px] text-red-500 mt-1 italic font-bold">Asegúrese de usar su nombre oficial para trámites.</p>
              </div>
              
              <div class="form-group">
                <label>Usuario de Acceso (Login)</label>
                <input v-model="user.name" type="text" class="form-input bg-slate-50 cursor-not-allowed font-mono text-slate-400" readonly>
              </div>

              <div class="form-group">
                <label>RFC Personal</label>
                <input v-model="user.rfc" type="text" class="form-input uppercase font-mono" placeholder="ABCD000000XXX">
              </div>

              <div class="form-group opacity-80">
                <label>Puesto / Cargo Administrativo</label>
                <input v-model="user.position" type="text" class="form-input bg-slate-100 font-bold" readonly>
              </div>

              <div class="form-group">
                <label>Correo Electrónico Laboral *</label>
                <input v-model="user.email" type="email" class="form-input" required>
              </div>
              
              <div class="form-group"><label>Teléfono Trabajo</label><input v-model="user.phone" type="tel" class="form-input"></div>
              <div class="form-group"><label>Teléfono Personal</label><input v-model="user.personal_phone" type="tel" class="form-input"></div>

              <hr class="col-span-full border-slate-100 my-4">

              <div class="form-group">
                <label>Estado / Región *</label>
                <select v-model="user.state_id" class="form-input" required>
                  <option value="">Seleccione...</option>
                  <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                </select>
              </div>

              <div class="form-group"><label>Ciudad</label><input v-model="user.city" type="text" class="form-input"></div>
              <div class="form-group col-span-full"><label>Dirección Completa de Domicilio</label><input v-model="user.address" type="text" class="form-input" placeholder="Calle, número, colonia..."></div>
            </div>

            <div class="mt-10 flex justify-end">
              <button type="submit" class="btn-primary-action" :disabled="loading">
                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save mr-2'"></i> 
                Actualizar Información
              </button>
            </div>
          </form>
        </section>

        <!-- SECCIÓN: HERRAMIENTAS DE TRABAJO -->
        <section v-if="activeSection === 'tools'" class="form-card shadow-premium animate-slide-up">
          <div class="section-title-box mb-8">
            <h2 class="form-title text-blue-900 border-blue-700">Herramientas del Trabajo</h2>
            <p class="text-xs text-slate-400 mt-1 uppercase font-black tracking-widest">Recursos y activos asignados</p>
          </div>

          <form @submit.prevent="confirmUpdateTools">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
              <div class="form-group"><label>Automóvil (Placas)</label><input v-model="user.car_plates" type="text" class="form-input uppercase font-mono"></div>
              <div class="form-group"><label>Tag de Telepeaje</label><input v-model="user.tag_number" type="text" class="form-input font-mono"></div>
              <div class="form-group col-span-full"><label>Póliza de Seguro</label><input v-model="user.insurance_policy" type="text" class="form-input"></div>
              <hr class="col-span-full border-slate-100 my-4">
              <div class="form-group"><label>Equipo Celular</label><input v-model="user.phone_model" type="text" class="form-input"></div>
              <div class="form-group"><label>Tablet</label><input v-model="user.tablet_model" type="text" class="form-input"></div>
              <div class="form-group"><label>Equipo Cómputo</label><input v-model="user.computer_model" type="text" class="form-input"></div>
              <div class="form-group"><label>Tarjeta Empresarial</label><input v-model="user.business_card" type="text" class="form-input font-mono"></div>
            </div>

            <div class="mt-10 flex justify-end">
              <button type="submit" class="btn-primary-action bg-blue-800 shadow-blue-100" :disabled="loading">
                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save mr-2'"></i> 
                Guardar Inventario
              </button>
            </div>
          </form>
        </section>

        <!-- SECCIÓN: SEGURIDAD -->
        <section v-if="activeSection === 'security'" class="form-card shadow-premium animate-slide-up max-w-2xl">
          <div class="section-title-box mb-8">
            <h2 class="form-title text-red-800 border-red-700">Seguridad</h2>
            <p class="text-xs text-slate-500 mt-1 uppercase font-black tracking-widest">Protección de acceso</p>
          </div>
          <form @submit.prevent="confirmUpdatePassword" class="space-y-6">
            <div class="form-group"><label>Contraseña Actual</label><input v-model="security.current_password" type="password" class="form-input" required></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="form-group"><label>Nueva Contraseña</label><input v-model="security.password" type="password" class="form-input" required></div>
              <div class="form-group"><label>Confirmar Nueva Contraseña</label><input v-model="security.password_confirmation" type="password" class="form-input" required></div>
            </div>
            
            <div class="mt-6">
              <button type="submit" class="btn-primary-action w-full" :disabled="loading">
                <i class="fas fa-key mr-2"></i> Cambiar Credenciales
              </button>
            </div>
          </form>
        </section>

        <!-- SECCIÓN: GESTIONAR DELEGADOS -->
        <section v-if="activeSection === 'delegates'" class="form-card shadow-premium animate-slide-up">
    <div class="flex justify-between items-center mb-8">
        <h2 class="form-title text-purple-800 border-purple-700">Gestionar Delegados</h2>
        <button v-if="!showAddDelegate" @click="showAddDelegate = true" class="btn-add-circle">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <div v-if="showAddDelegate" class="bg-red-50 p-6 rounded-3xl border-2 border-red-100 mb-8 animate-fade-in shadow-inner">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="text-[10px] font-black uppercase mb-2 block text-red-800">Usuario Delegado</label>
                <input v-model="newDelegate.username" type="text" class="form-input text-sm" placeholder="Ej. juan.perez">
            </div>
            <div>
                <label class="text-[10px] font-black uppercase mb-2 block text-red-800">Contraseña Temporal</label>
                <input v-model="newDelegate.password" type="password" class="form-input text-sm" placeholder="••••••••">
            </div>
            
            <div class="flex gap-2">
                <button @click="addDelegate" class="btn-save-profile py-3 px-6 flex-1 text-sm">Crear Cuenta</button>
                <br><br>
                <button @click="showAddDelegate = false" class="btn-secondary bg-white text-slate-400 p-2 rounded-xl border-2 hover:bg-slate-50 transition-colors">
                  
                    <i class="fas fa-times"></i>Quitar
                </button>
            </div>
        </div>
    </div>

    <div v-if="delegates.length === 0" class="empty-delegates text-center py-20 border-2 border-dashed border-slate-200 rounded-[2.5rem] bg-slate-50/50">
        <i class="fas fa-user-shield text-4xl text-slate-200 mb-4"></i>
        <p class="text-slate-400 font-bold italic">No hay cuentas de delegados autorizadas.</p>
    </div>
    
    <div v-else class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm">
        <table class="min-width-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="table-header w-16 text-center">Inic.</th>
                    <th class="table-header">Nombre de Usuario</th>
                    <th class="table-header">Rol / Permisos</th>
                    <th class="table-header text-center">Estado</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                <tr v-for="delegate in delegates" :key="delegate.id" class="hover:bg-gray-50 transition-colors">
                    <td class="table-cell">
                        <div class="w-10 h-10 bg-slate-100 text-slate-500 rounded-full flex items-center justify-center font-black text-xs mx-auto border border-slate-200">
                            {{ delegate.name.charAt(0).toUpperCase() }}
                        </div>
                    </td>

                    <td class="table-cell">
                        <div class="text-sm font-black text-slate-800 uppercase leading-tight">
                            {{ delegate.name }}
                        </div>
                        <div class="text-[9px] text-slate-400 font-bold mt-1 tracking-widest">
                            ID: #00{{ delegate.id }}
                        </div>
                    </td>

                    <td class="table-cell">
                        <span class="text-[10px] font-black text-purple-700 bg-purple-50 px-3 py-1 rounded-lg border border-purple-100 uppercase tracking-tighter">
                            Delegado Autorizado
                        </span>
                    </td>

                    <td class="table-cell text-center">
                        <span class="status-badge-sm">
                            <i class="fas fa-circle text-[6px] text-green-500 mr-2"></i> ACTIVO
                        </span>
                    </td>

                    <td class="table-cell text-right">
                        <button @click="confirmRemoveDelegate(delegate)" 
                                class="btn-delete-delegate" 
                                title="Revocar acceso">
                            <i class="fas fa-trash-alt"></i>
                            <span class="hidden md:inline ml-1">Revocar</span>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

      </main>    
    </div>

    <!-- MODAL DE SISTEMA (OVERLAY TIPO PEDIDOS) -->
    <Teleport to="body">
      <Transition name="modal-pop">
        <div v-if="modal.visible" class="modal-overlay-wrapper">
          
          <!-- ESTILO PARA CONFIRMACIÓN -->
          <div v-if="modal.type !== 'success'" class="form-card bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 animate-scale-in">
            <div :class="['h-2 w-full', modal.type === 'danger' ? 'bg-red-600' : 'bg-blue-600']"></div>
            <div class="p-10 text-center">
              <div :class="['w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg', modal.type === 'danger' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600']">
                <i :class="['fas fa-2xl', modal.type === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle']"></i>
              </div>
              <h3 class="text-2xl font-black text-slate-800 leading-tight mb-2 uppercase tracking-tighter">{{ modal.title }}</h3>
              <p class="text-slate-500 text-sm leading-relaxed font-medium">{{ modal.message }}</p>
            </div>
            <div class="bg-slate-50 p-6 flex gap-3 border-t">
              <button v-if="modal.confirm" @click="closeModal" class="btn-primary-action1 flex-1 py-4 text-xs font-black text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">Cancelar</button>
              <button @click="handleModalConfirm" class="btn-primary-action flex-1 py-4 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl transition-all active:scale-95" :class="modal.type === 'danger' ? 'bg-red-600 text-white' : 'bg-slate-900 text-white'">
                {{ modal.confirm ? 'Confirmar' : 'Aceptar' }}
              </button>
            </div>
          </div>

          <!-- ESTILO PARA ÉXITO (COMO EL DE PEDIDOS) -->
          <div v-else class="modal-content-success animate-scale-in">
            <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
            <h2 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tighter">{{ modal.title }}</h2>
            <p class="text-sm text-slate-500 mb-8 font-medium px-4">{{ modal.message }}</p>
            <button @click="closeModal" class="btn-primary-action w-full py-4 bg-slate-900">Continuar</button>
          </div>

        </div>
      </Transition>
    </Teleport>

    <!-- TOAST RÁPIDO -->
    <Transition name="toast">
      <div v-if="toast.visible" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-[9999]">
        <div class="bg-slate-900 text-white px-10 py-4 rounded-full shadow-2xl flex items-center gap-4 border border-slate-700 backdrop-blur-md">
          <i class="fas fa-check-circle text-green-400 text-xl"></i>
          <span class="font-bold tracking-tight">{{ toast.message }}</span>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from '../../axios'

const router = useRouter()
const loading = ref(false)
const loadingData = ref(true)
const activeSection = ref('personal')
const showAddDelegate = ref(false)

const user = ref({
    name: '',
    full_name: '',
    rfc: '',
    email: '',
    phone: '',
    personal_phone: '',
    position: '',
    employee_id: '',
    state_id: '',
    city: '',
    address: '',
    car_plates: '',
    tag_number: '',
    insurance_policy: '',
    phone_model: '',
    tablet_model: '',
    computer_model: '',
    business_card: '',
})
const estados = ref([])
const delegates = ref([])
const security = reactive({ current_password: '', password: '', password_confirmation: '' })
const newDelegate = reactive({ username: '', password: '' })

const modal = reactive({ visible: false, title: '', message: '', type: 'info', confirm: null })
const toast = reactive({ visible: false, message: '' })

const showToast = (msg) => {
  toast.message = msg; toast.visible = true;
  setTimeout(() => toast.visible = false, 4000)
}

const openModal = (title, message, type = 'info', confirmCallback = null) => {
  modal.title = title; modal.message = message; modal.type = type; modal.confirm = confirmCallback; modal.visible = true;
}

const closeModal = () => { modal.visible = false }

const handleModalConfirm = () => {
  if (modal.confirm) modal.confirm()
  else closeModal()
}

const fetchInitialData = async () => {
  loadingData.value = true
  try {
    const [estadosRes, profileRes] = await Promise.all([
      axios.get('profile/estados'),
      axios.get('profile/myprofile')
    ])
    estados.value = estadosRes.data
    
    const dbData = profileRes.data.user || profileRes.data
    
    // HIDRATACIÓN: Muestra el nombre real si existe.
    user.value = {
        name: dbData.name || '',
        full_name: dbData.full_name || '', 
        email: dbData.email || '',
        rfc: dbData.rfc || '',
        phone: dbData.phone || '',
        personal_phone: dbData.personal_phone || '',
        position: dbData.position || '',
        employee_id: dbData.employee_id || '',
        state_id: dbData.state_id || '',
        city: dbData.city || '',
        address: dbData.address || '',
        car_plates: dbData.car_plates || '',
        tag_number: dbData.tag_number || '',
        insurance_policy: dbData.insurance_policy || '',
        phone_model: dbData.phone_model || '',
        tablet_model: dbData.tablet_model || '',
        computer_model: dbData.computer_model || '',
        business_card: dbData.business_card || '',
    }
    
    delegates.value = dbData.delegates || []
    
  } catch (e) {
    openModal('Sincronización Interrumpida', 'No se pudieron recuperar los datos de tu perfil profesional.', 'danger')
  } finally {
    loadingData.value = false
  }
}

/* ------------------ ACCIONES CON MODALES DE CONFIRMACIÓN ------------------ */

const confirmUpdateProfile = () => {
  openModal(
    'Confirmar Actualización',
    '¿Deseas guardar los cambios realizados en tu identidad y ubicación personal?',
    'info',
    updateProfile
  )
}

const updateProfile = async () => {
  loading.value = true
  closeModal()
  try {
    // ⚠️ RECUERDA: El modelo User.php en Laravel DEBE tener 'full_name' en el arreglo $fillable
    const payload = {
        full_name:      user.value.full_name,
        email:          user.value.email,
        rfc:            user.value.rfc,
        phone:          user.value.phone,
        personal_phone: user.value.personal_phone,
        state_id:       user.value.state_id || null,
        city:           user.value.city,
        address:        user.value.address,
    };

    await axios.put('profile', payload)
    openModal('¡Perfil Actualizado!', 'Tu información profesional se ha guardado con éxito en el sistema.', 'success')
    await fetchInitialData()
  } catch (e) {
    if (e.response?.status === 422) {
        const firstError = Object.values(e.response.data.errors)[0][0];
        openModal('Validación Fallida', firstError, 'warning');
    } else {
        openModal('Error de Guardado', 'El servidor rechazó la solicitud. Verifica tu conexión.', 'danger')
    }
  } finally {
    loading.value = false
  }
}

const confirmUpdateTools = () => {
  openModal(
    'Sincronizar Inventario',
    '¿Los datos de las herramientas y activos asignados son correctos?',
    'info',
    updateTools
  )
}

const updateTools = async () => {
  loading.value = true
  closeModal()
  try {
    const payload = {
        car_plates:       user.value.car_plates,
        tag_number:       user.value.tag_number,
        insurance_policy: user.value.insurance_policy,
        phone_model:      user.value.phone_model,
        tablet_model:     user.value.tablet_model,
        computer_model:   user.value.computer_model,
        business_card:    user.value.business_card,
    };
    await axios.put('profile/tools', payload)
    openModal('¡Inventario Guardado!', 'Los datos de tus herramientas de trabajo se han actualizado.', 'success')
    await fetchInitialData()
  } catch (e) { 
    openModal('Error de Herramientas', 'No se pudieron guardar los datos del inventario.', 'danger') 
  } finally { 
    loading.value = false 
  }
}

const confirmUpdatePassword = () => {
  if (!security.current_password || !security.password) {
    return openModal('Faltan Datos', 'Debes completar los campos de contraseña obligatorios.', 'warning')
  }
  openModal(
    'Cambio de Seguridad',
    'Deberás usar tus nuevas credenciales en el próximo inicio de sesión. ¿Confirmar?',
    'warning',
    updatePassword
  )
}

const updatePassword = async () => {
  if (security.password !== security.password_confirmation) {
    return openModal('Validación', 'La confirmación de la nueva contraseña no coincide.', 'warning')
  }
  loading.value = true
  closeModal()
  try {
    await axios.put('profile/password', security)
    openModal('¡Acceso Seguro!', 'Tu contraseña ha sido modificada correctamente.', 'success')
    security.current_password = security.password = security.password_confirmation = ''
  } catch (e) { 
    openModal('Error de Contraseña', 'La contraseña actual ingresada es incorrecta.', 'danger') 
  } finally { 
    loading.value = false 
  }
}

const addDelegate = async () => {
  if (!newDelegate.username || !newDelegate.password) return openModal('Datos Incompletos', 'Escribe un usuario y contraseña para el delegado.', 'warning')
  loading.value = true
  try {
    const res = await axios.post('profile/delegates', newDelegate)
    delegates.value.push(res.data.delegate)
    newDelegate.username = ''; newDelegate.password = ''; showAddDelegate.value = false
    openModal('¡Delegado Autorizado!', 'La cuenta secundaria ha sido creada y vinculada.', 'success')
  } catch (e) { 
    openModal('Error de Cuenta', 'No se pudo generar la cuenta delegada. El usuario podría ya existir.', 'danger') 
  } finally { 
    loading.value = false 
  }
}

const confirmRemoveDelegate = (delegate) => {
  openModal('¿Revocar Acceso?', `Estás por eliminar el permiso para "${delegate.name}". ¿Deseas continuar?`, 'danger', () => removeDelegate(delegate.id))
}

const removeDelegate = async (id) => {
  closeModal()
  try {
    await axios.delete(`profile/delegates/${id}`)
    delegates.value = delegates.value.filter(d => d.id !== id)
    showToast('Acceso revocado correctamente')
  } catch (e) { 
    openModal('Error de Revocación', 'Hubo un fallo al intentar eliminar el acceso.', 'danger') 
  }
}

onMounted(fetchInitialData)
</script>

<style scoped>
.profile-page-wrapper { background: radial-gradient(circle at top right, #fff1f2 0%, #f8fafc 40%); }
.inner-nav-card { background: white; border-radius: 30px; overflow: hidden; border: 1px solid #f1f5f9; }
.inner-nav-item { width: 100%; display: flex; align-items: center; gap: 16px; padding: 16px 24px; border-radius: 20px; font-size: 0.9rem; font-weight: 700; color: #64748b; border: none; background: transparent; text-align: left; cursor: pointer; transition: 0.3s ease; }
.inner-nav-item:hover { background: #fdf2f2; color: #a93339; }
.inner-nav-item.active { background: #edb6b9; color: white; box-shadow: 0 10px 20px -5px rgba(169, 51, 57, 0.3); }

.form-card { background: white; border-radius: 40px; padding: 40px; border: 1px solid #f1f5f9; }
.form-title { font-size: 1.5rem; font-weight: 900; color: #1e293b; border-left: 6px solid #a93339; padding-left: 20px; text-transform: uppercase; letter-spacing: -0.025em; }

.form-group label { display: block; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px; letter-spacing: 0.1em; }
.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: all 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #a93339; background: white; outline: none; box-shadow: 0 0 0 4px rgba(169, 51, 57, 0.05); }

.btn-primary-action1 { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; padding: 16px 45px; border-radius: 18px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary-action1:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-primary-action { background: linear-gradient(135deg, #33a941 0%, #138838 100%); color: white; padding: 16px 45px; border-radius: 18px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary-action:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }


.btn-save-profile { background: #1e293b; color: white; padding: 12px 24px; border-radius: 14px; font-weight: 800; border: none; cursor: pointer; }
.btn-add-circle { width: 45px; height: 45px; background: #a93339; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; transition: transform 0.2s; }
.btn-add-circle:hover { transform: scale(1.1) rotate(90deg); }

.btn-exit-profile { background: white; border: 2px solid #f1f5f9; color: #64748b; padding: 10px 20px; border-radius: 14px; font-weight: 800; cursor: pointer; font-size: 0.8rem; }

.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }

/* --- ESTILOS PARA EL OVERLAY DEL MODAL (SOLUCIÓN) --- */
.modal-overlay-wrapper {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background-color: rgba(15, 23, 42, 0.8);
    backdrop-filter: blur(4px);
    overflow: hidden;
}

/* SUCCESS MODAL SPECIFIC (COMO PEDIDOS) */
.modal-content-success { background: white; padding: 45px; border-radius: 40px; text-align: center; width: 90%; max-width: 400px; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 75px; height: 75px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 25px; border: 4px solid white; }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-slide-up { animation: slideUp 0.5s ease-out; }
@keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.modal-pop-enter-active, .modal-pop-leave-active { transition: all 0.3s ease; }
.modal-pop-enter-from, .modal-pop-leave-to { opacity: 0; }

.toast-enter-active, .toast-leave-active { transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 100%); }

.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
}

table {
    width: 100%;
    border-collapse: collapse;
}

/* Cabeceras */
.table-header {
    padding: 14px 16px;
    font-size: 0.7rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

/* Celdas */
.table-cell {
    padding: 12px 16px;
    vertical-align: middle;
}

/* Badge de Estatus Sutil */
.status-badge-sm {
    font-size: 9px;
    font-weight: 800;
    color: #1e293b;
    display: inline-flex;
    align-items: center;
}

/* Botón de Eliminación */
.btn-delete-delegate {
    background: none;
    border: none;
    color: #cbd5e1; /* Slate-300 */
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 8px 12px;
}

.btn-delete-delegate:hover {
    color: #dc2626; /* Red-600 */
    background-color: #fef2f2;
    border-radius: 10px;
}

/* Estilos de botones de acción superior (asumiendo que ya tienes algunos) */
.btn-add-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #b91c1c;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s;
}

.btn-add-circle:hover {
    transform: rotate(90deg);
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #f1f5f9;
    border-radius: 12px;
    transition: all 0.3s;
}

.form-input:focus {
    outline: none;
    border-color: #f87171;
    background: white;
}

.table-shadow-lg {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
}

.text-right { text-align: right; }
.text-center { text-align: center; }
</style>