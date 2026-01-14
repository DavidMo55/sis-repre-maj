<template>
  <div class="profile-page-wrapper min-h-screen bg-slate-50 animate-fade-in">
    <!-- Header Especial de Perfil (Inmersivo) -->
    <header class="profile-header bg-white shadow-sm border-b border-slate-100 sticky top-0 z-50">
      <div class="header-container max-w-7xl mx-auto px-6 py-6 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-black text-slate-800 tracking-tight flex items-center gap-3">
            <i class="fas fa-id-card text-red-700"></i> Mi Perfil Profesional
          </h1>
        </div>
      </div>
    </header>

    <!-- Estado de Carga Inicial -->
    <div v-if="loadingData" class="flex flex-col items-center justify-center py-40 animate-pulse">
        <i class="fas fa-circle-notch fa-spin text-5xl text-red-700 mb-4"></i>
    </div>

    

    <div v-else class="max-w-7xl mx-auto px-6 flex flex-col lg:flex-row gap-8 py-10 pb-20">
      
      <!-- MENU LATERAL DE PERFIL (SIDEBAR INTERNO) -->
      <aside class="w-full lg:w-80 shrink-0">
        <div class="inner-nav-card shadow-premium sticky top-28">
          <div class="user-avatar-brief p-8 border-b border-slate-50 flex flex-col items-center text-center">
            <div class="w-24 h-24 bg-gradient-to-tr from-red-800 to-red-600 rounded-full flex items-center justify-center text-white text-4xl font-black mb-4 shadow-xl border-4 border-white ring-4 ring-red-50">
            </div>
           
          </div>
          
          <nav class="p-4 space-y-2">
            <button @click="activeSection = 'personal'" :class="['inner-nav-item', { 'active': activeSection === 'personal' }]">
              <i class="fas fa-user-edit"></i>
              <span>Datos Personales</span>
            </button>
            <button @click="activeSection = 'tools'" :class="['inner-nav-item', { 'active': activeSection === 'tools' }]">
              <i class="fas fa-tools"></i>
              <span>Herramientas de Trabajo</span>
            </button>
            <button @click="activeSection = 'security'" :class="['inner-nav-item', { 'active': activeSection === 'security' }]">
              <i class="fas fa-shield-alt"></i>
              <span>Seguridad</span>
            </button>
            <button @click="activeSection = 'delegates'" :class="['inner-nav-item', { 'active': activeSection === 'delegates' }]">
              <i class="fas fa-users-cog"></i>
              <span>Gestionar Delegados</span>
            </button>
          </nav>

          <div class="p-6 bg-slate-50 rounded-b-3xl text-center border-t border-slate-100">
          </div>
        </div>
      </aside>

      <!-- ÁREA PRINCIPAL DE CONTENIDO -->
      <main class="flex-1 min-w-0">
        
        <!-- SECCIÓN: DATOS PERSONALES -->
        <section v-if="activeSection === 'personal'" class="form-card shadow-premium animate-slide-up">
          <div class="section-title-box mb-8">
            <h2 class="form-title">Identidad y Ubicación</h2>
            <p class="text-xs text-slate-400 mt-1 uppercase font-black tracking-widest">Información oficial y de contacto</p>
          </div>

          <form @submit.prevent="updateProfile" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
            <div class="form-group col-span-full md:col-span-1">
              <label>Nombre Completo</label>
              <input v-model="user.name" type="text" class="form-input" placeholder="Nombre completo" required>
            </div>
            
            <div class="form-group">
              <label>RFC Personal</label>
              <input v-model="user.rfc" type="text" class="form-input uppercase font-mono" placeholder="ABCD000000XXX">
            </div>

            <!-- CAMPO DE CARGO (SOLO LECTURA) -->
            <div class="form-group">
              <label>Puesto / Cargo Administrativo (No Editable)</label>
              <div class="relative">
                <input v-model="user.position" type="text" class="form-input bg-slate-100 cursor-not-allowed font-bold" readonly>
                <i class="fas fa-lock absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              </div>
            </div>

            <div class="form-group">
              <label>ID de Empleado (No Editable)</label>
              <input v-model="user.employee_id" type="text" class="form-input bg-slate-100 cursor-not-allowed font-mono" readonly>
            </div>

            <div class="form-group">
              <label>Correo Electrónico Laboral</label>
              <input v-model="user.email" type="email" class="form-input" placeholder="correo@empresa.com" required>
            </div>

            <div class="form-group">
              <label>Teléfono Celular (Trabajo)</label>
              <input v-model="user.phone" type="tel" class="form-input" placeholder="55 0000 0000">
            </div>

            <div class="form-group">
              <label>Teléfono Personal (Privado)</label>
              <input v-model="user.personal_phone" type="tel" class="form-input" placeholder="55 0000 0000">
            </div>

            <hr class="col-span-full border-slate-100 my-4">

            <div class="form-group">
              <label>Estado / Región</label>
              <select v-model="user.state_id" class="form-input" required>
                <option value="">Seleccione un estado...</option>
                <option v-for="e in estados" :key="e.id" :value="e.id">
                  {{ e.estado }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Ciudad de Residencia</label>
              <input v-model="user.city" type="text" class="form-input" placeholder="Ej: Pachuca de Soto">
            </div>

            <div class="form-group col-span-full">
              <label>Dirección Completa (Domicilio)</label>
              <input v-model="user.address" type="text" class="form-input" placeholder="Calle, Número, Colonia, CP...">
            </div>

            <div class="col-span-full flex justify-end pt-6 border-t mt-4">
              <button type="submit" class="btn-save-profile" :disabled="loading">
                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                {{ loading ? 'Sincronizando...' : 'Guardar Información' }}
              </button>
            </div>
          </form>
        </section>

<!-- SECCIÓN: HERRAMIENTAS DE TRABAJO -->
        <section v-if="activeSection === 'tools'" class="form-card shadow-premium animate-slide-up">
          <div class="section-title-box mb-8">
            <h2 class="form-title text-blue-900 border-blue-700">Herramientas del Trabajo</h2>
            <p class="text-xs text-slate-400 mt-1 uppercase font-black tracking-widest">Activos y recursos asignados</p>
          </div>

          <form @submit.prevent="updateTools" class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
            <div class="form-group">
              <label><i class="fas fa-car mr-1 text-slate-400"></i> Automóvil (Placas)</label>
              <input v-model="user.car_plates" type="text" class="form-input uppercase font-mono" placeholder="ABC-123-A">
            </div>
            
            <div class="form-group">
              <label><i class="fas fa-barcode mr-1 text-slate-400"></i> Tag de Telepeaje</label>
              <input v-model="user.tag_number" type="text" class="form-input font-mono" placeholder="12345678">
            </div>

            <div class="form-group col-span-full">
              <label><i class="fas fa-file-contract mr-1 text-slate-400"></i> Póliza de Seguro (Folio / Empresa)</label>
              <input v-model="user.insurance_policy" type="text" class="form-input" placeholder="Número de póliza y aseguradora">
            </div>

            <hr class="col-span-full border-slate-100 my-4">

            <div class="form-group">
              <label><i class="fas fa-mobile-alt mr-1 text-slate-400"></i> Equipo Celular</label>
              <input v-model="user.phone_model" type="text" class="form-input" placeholder="Modelo y Número de Serie">
            </div>

            <div class="form-group">
              <label><i class="fas fa-tablet-alt mr-1 text-slate-400"></i> Tablet</label>
              <input v-model="user.tablet_model" type="text" class="form-input" placeholder="Modelo y Número de Serie">
            </div>

            <div class="form-group">
              <label><i class="fas fa-laptop mr-1 text-slate-400"></i> Equipo de Cómputo</label>
              <input v-model="user.computer_model" type="text" class="form-input" placeholder="Marca y especificaciones">
            </div>

            <div class="form-group">
              <label><i class="fas fa-credit-card mr-1 text-slate-400"></i> Tarjeta Empresarial</label>
              <input v-model="user.business_card" type="text" class="form-input font-mono" placeholder="Referencia / Terminación">
            </div>

            <div class="col-span-full flex justify-end pt-6 border-t mt-4">
              <button type="submit" class="btn-save-profile bg-blue-800 shadow-blue-200" :disabled="loading">
                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                {{ loading ? 'Actualizando...' : 'Guardar Inventario' }}
              </button>
            </div>
          </form>
        </section>

        <!-- SECCIÓN: SEGURIDAD -->
        <section v-if="activeSection === 'security'" class="form-card shadow-premium animate-slide-up max-w-2xl">
          <div class="section-title-box mb-8">
            <h2 class="form-title text-red-800">Seguridad de la Cuenta</h2>
            <p class="text-xs text-slate-500 mt-1 uppercase font-black tracking-widest">Protección de credenciales</p>
          </div>

          <form @submit.prevent="updatePassword" class="space-y-6">
            <div class="form-group">
              <label>Contraseña Actual</label>
              <input v-model="security.current_password" type="password" class="form-input" placeholder="••••••••" required>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="form-group">
                <label>Nueva Contraseña</label>
                <input v-model="security.password" type="password" class="form-input" placeholder="Mínimo 8 caracteres" required>
              </div>
              <div class="form-group">
                <label>Confirmar Contraseña</label>
                <input v-model="security.password_confirmation" type="password" class="form-input" placeholder="Repita la contraseña" required>
              </div>
            </div>

            <div class="pt-4">
              <button type="submit" class="btn-save-profile w-full" :disabled="loading">
                <i class="fas fa-key mr-2"></i> Actualizar Acceso
              </button>
            </div>
          </form>
        </section>

        <!-- SECCIÓN: GESTIONAR DELEGADOS -->
        <section v-if="activeSection === 'delegates'" class="form-card shadow-premium animate-slide-up">
          <div class="section-title-box mb-8">
            <h2 class="form-title text-purple-800">Gestionar Delegados</h2>
            <p class="text-xs text-slate-400 mt-1 uppercase font-black tracking-widest">Autorizar cuentas de acceso</p>
          </div>

          <button v-if="!showAddDelegate" @click="showAddDelegate = true" class="btn-add-circle mb-8">
            <i class="fas fa-plus text-xl"></i>+
          </button>

          <!-- FORMULARIO DE CREACIÓN -->
          <Transition name="fade">
            <div v-if="showAddDelegate" class="bg-red-50 p-6 rounded-2xl border-2 border-red-100 mb-8 animate-fade-in">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="text-[10px] font-black uppercase mb-2 block text-red-800">Nombre de Usuario</label>
                        <input v-model="newDelegate.username" type="text" class="form-input text-sm" placeholder="Ej: asistente_mkt">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase mb-2 block text-red-800">Contraseña de Acceso</label>
                        <input v-model="newDelegate.password" type="password" class="form-input text-sm" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="flex gap-2">
                    <button @click="addDelegate" class="btn-save-profile py-2 px-6 flex-1" :disabled="loading">
                        {{ loading ? 'Creando...' : 'Crear Cuenta' }}
                    </button>
                    <button @click="showAddDelegate = false" class="bg-white text-slate-400 p-2 rounded-xl border-2 hover:bg-slate-50">
                        <i class="fas fa-times"></i>Cancelar
                    </button>
                    </div>
                </div>
            </div>
          </Transition>

          <div v-if="delegates.length === 0" class="empty-delegates text-center py-20 border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50/50">
            <i class="fas fa-user-shield text-4xl text-slate-200 mb-4"></i>
            <p class="text-slate-400 font-bold italic">No hay cuentas de delegados registradas.</p>
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="delegate in delegates" :key="delegate.id" class="delegate-item p-5 bg-white border-2 border-slate-50 rounded-2xl flex items-center justify-between group hover:border-red-100 transition-all">
              <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 font-bold">
                    {{ delegate.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <p class="text-sm font-black text-slate-800">{{ delegate.name }}</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-tighter">Usuario con acceso</p>
                </div>
              </div>
              <button @click="removeDelegate(delegate.id)" class="text-slate-300 hover:text-red-600 p-3 transition-colors" title="Eliminar acceso">
                <i class="fas fa-trash-alt"></i>Eliminar
              </button>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- TOAST -->
    <Transition name="toast">
      <div v-if="successMessage" class="toast-feedback fixed bottom-10 left-1/2 -translate-x-1/2 z-[9999]">
        <div class="bg-slate-900 text-white px-10 py-4 rounded-full shadow-2xl flex items-center gap-4 border border-slate-700 backdrop-blur-md">
          <i class="fas fa-check-circle text-green-400 text-xl"></i>
          <span class="font-bold tracking-tight">{{ successMessage }}</span>
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

/* ------------------ STATE ------------------ */
const activeSection = ref('personal')
const loading = ref(false)
const loadingData = ref(true)
const apiError = ref(null) // Para capturar el 500
const successMessage = ref('')
const showAddDelegate = ref(false)

const estados = ref([])
const delegates = ref([])

const user = ref({
  name: '', rfc: '', email: '', phone: '', personal_phone: '', position: '', employee_id: '',
  state_id: '', city: '', address: '', car_plates: '', tag_number: '', insurance_policy: '',
  phone_model: '', tablet_model: '', computer_model: '', business_card: '',
})

const security = reactive({ current_password: '', password: '', password_confirmation: '' })

const newDelegate = reactive({
  username: '',
  password: '',
})
/* ------------------ HELPERS ------------------ */
const showToast = (msg) => {
  successMessage.value = msg
  setTimeout(() => (successMessage.value = ''), 4000)
}

/* ------------------ FETCH INICIAL ROBUSTO ------------------ */
const fetchInitialData = async () => {
  loadingData.value = true
  apiError.value = null
  try {
    // 1. Cargamos catálogos (estados) primero
    const estadosRes = await axios.get('profile/estados');
    estados.value = Array.isArray(estadosRes.data) ? estadosRes.data : [];

    // 2. Cargamos el perfil real desde /api/profile/myprofile
    const profileRes = await axios.get('profile/myprofile');
    
    // Si llegamos aquí, la respuesta fue exitosa (200 OK)
    const dbData = profileRes.data.user || profileRes.data;

    // 3. Hidratación INDIVIDUAL EXPLÍCITA (Garantiza el autorellenado)
    user.value = {
        name: dbData.name || '',
        email: dbData.email || '',
        rfc: dbData.rfc || '',
        phone: dbData.phone || '',
        personal_phone: dbData.personal_phone || '',
        position: dbData.position || 'Representante de Majestic',
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
    };

    // 4. Cargamos delegados
    delegates.value = dbData.delegates || [];
    
    console.log("HIDRATACIÓN COMPLETADA CON ÉXITO:", user.value);

  } catch (e) {
    console.error('ERROR CRÍTICO AL CARGAR PERFIL:', e);
    // Capturamos el mensaje de error para mostrarlo en la UI
    apiError.value = e.response?.data?.message || e.message || 'Error desconocido del servidor';
  } finally {
    loadingData.value = false;
  }
}

/* ------------------ ACTIONS ------------------ */
const updateProfile = async () => {
  loading.value = true
  try {
    await axios.put('profile', user.value)
    showToast('Información actualizada')
    await fetchInitialData() 
  } catch (e) { alert('Error al guardar') } finally { loading.value = false }
}

const updateTools = async () => {
  loading.value = true
  try {
    await axios.put('profile/tools', user.value)
    showToast('Inventario actualizado')
    await fetchInitialData()
  } catch (e) { alert('Error al guardar') } finally { loading.value = false }
}

const updatePassword = async () => {
  if (security.password !== security.password_confirmation) return alert('No coinciden')
  loading.value = true
  try {
    await axios.put('profile/password', security)
    showToast('Seguridad actualizada')
    security.current_password = security.password = security.password_confirmation = ''
  } catch (e) { alert('Fallo al cambiar contraseña') } finally { loading.value = false }
}

const addDelegate = async () => {
  if (!newDelegate.username || !newDelegate.password) return alert("Completa usuario y contraseña");
  
  loading.value = true;
  try {
    const res = await axios.post('profile/delegates', newDelegate)
    if (res.data.delegate) {
        delegates.value.push(res.data.delegate)
        newDelegate.username = ''; 
        newDelegate.password = ''; 
        showAddDelegate.value = false
        showToast('Cuenta de delegado creada con éxito')
    }
  } catch (e) { 
    alert(e.response?.data?.message || 'Error al crear la cuenta. Puede que el usuario ya exista.') 
  } finally {
    loading.value = false;
  }
}

const removeDelegate = async (id) => {
  if (!confirm('¿Deseas revocar el acceso?')) return
  try {
    await axios.delete(`profile/delegates/${id}`)
    delegates.value = delegates.value.filter(d => d.id !== id)
    showToast('Acceso revocado')
  } catch (e) { alert('Error al eliminar') }
}

onMounted(fetchInitialData)
</script>

<style scoped>
.profile-page-wrapper { background: radial-gradient(circle at top right, #fff1f2 0%, #f8fafc 40%); }
.inner-nav-card { background: white; border-radius: 30px; overflow: hidden; border: 1px solid #f1f5f9; }
.inner-nav-item { width: 100%; display: flex; align-items: center; gap: 16px; padding: 16px 24px; border-radius: 20px; font-size: 0.95rem; font-weight: 700; color: #64748b; border: none; background: transparent; text-align: left; cursor: pointer; transition: 0.3s; }
.inner-nav-item.active { background: #ffb8bc; color: white; box-shadow: 0 10px 20px -5px rgba(169, 51, 57, 0.3); }
.form-card { background: white; border-radius: 40px; padding: 50px; border: 1px solid #f1f5f9; }
.form-title { font-size: 1.75rem; font-weight: 900; color: #1e293b; border-left: 6px solid #a93339; padding-left: 24px; }
.form-group label { display: block; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; color: #94a3b8; margin-bottom: 10px; }
.form-input { width: 100%; padding: 16px 20px; border-radius: 18px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; }
.btn-save-profile { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; padding: 18px 40px; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; }
.btn-add-circle { width: 50px; height: 50px; background: #a93339; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.animate-slide-up { animation: slideUp 0.6s ease-out; }
@keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
</style>
