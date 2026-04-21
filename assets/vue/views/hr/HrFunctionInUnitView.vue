<template>
  <div class="p-4">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">{{ t("Function-unit associations") }}</h1>
      <BaseButton
        type="success"
        icon="plus-box"
        :label="t('Add association')"
        @click="openCreate"
      />
    </div>

    <BaseTable :values="items" :is-loading="isLoading">
      <Column field="title" :header="t('Title')" sortable />
      <Column field="professionalFunctionTitle" :header="t('Professional function')" sortable />
      <Column field="businessUnitTitle" :header="t('Business unit')" sortable />
      <Column field="geographicZoneTitle" :header="t('Geographic zone')" />
      <Column :header="t('Actions')" style="width: 100px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <BaseButton
              type="secondary-text"
              icon="pencil"
              only-icon
              size="small"
              @click="openEdit(data)"
            />
            <BaseButton
              type="danger-text"
              icon="delete"
              only-icon
              size="small"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <Dialog
      v-model:visible="dialogVisible"
      :header="editingItem ? t('Edit association') : t('Add association')"
      modal
      :style="{ width: '560px' }"
    >
      <div class="flex flex-col gap-3 pt-2">
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Title") }} *</label>
          <input
            v-model="form.title"
            name="fiu_title"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Description") }}</label>
          <textarea
            v-model="form.description"
            name="fiu_description"
            rows="2"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Professional function") }} *</label>
          <select
            v-model="form.professionalFunction"
            name="fiu_professional_function"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option value="">— {{ t("Select") }} —</option>
            <option v-for="fn in professionalFunctions" :key="fn['@id']" :value="fn['@id']">
              {{ fn.title }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Business unit") }} *</label>
          <select
            v-model="form.businessUnit"
            name="fiu_business_unit"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option value="">— {{ t("Select") }} —</option>
            <option v-for="bu in businessUnits" :key="bu['@id']" :value="bu['@id']">
              {{ bu.title }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">{{ t("Geographic zone") }}</label>
          <select
            v-model="form.geographicZone"
            name="fiu_geographic_zone"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
          >
            <option :value="null">— {{ t("None") }} —</option>
            <option v-for="gz in geographicZones" :key="gz['@id']" :value="gz['@id']">
              {{ gz.title }}
            </option>
          </select>
        </div>
        <div v-if="editingItem">
          <label class="block text-sm font-medium mb-2">{{ t("Activities") }}</label>
          <div class="flex flex-wrap gap-2 mb-2">
            <span
              v-for="act in form.activities"
              :key="act"
              class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded flex items-center gap-1"
            >
              {{ activityLabel(act) }}
              <button type="button" class="hover:text-red-500" @click="removeActivity(act)">×</button>
            </span>
          </div>
          <select
            name="fiu_add_activity"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            @change="addActivity($event)"
          >
            <option value="">{{ t("Add activity…") }}</option>
            <option v-for="act in availableActivities" :key="act['@id']" :value="act['@id']">
              {{ act.title }}
            </option>
          </select>
        </div>
        <div v-if="editingItem">
          <label class="block text-sm font-medium mb-2">{{ t("Required skills") }}</label>
          <div class="space-y-1 mb-2">
            <div
              v-for="(entry, idx) in form.skills"
              :key="idx"
              class="flex gap-2 items-center text-sm"
            >
              <Select
                v-model="form.skills[idx].skill"
                :options="skills"
                option-label="title"
                option-value="@id"
                filter
                :placeholder="t('— Skill —')"
                :filter-placeholder="t('Search…')"
                class="flex-1 text-sm"
                :pt="{ root: { name: 'fiu_skill' } }"
              />
              <select
                v-model="form.skills[idx].level"
                name="fiu_skill_level"
                class="border border-gray-300 rounded px-2 py-1 text-sm flex-1"
              >
                <option :value="null">— {{ t("Any level") }} —</option>
                <option v-for="lv in levels" :key="lv['@id']" :value="lv['@id']">{{ lv.title }}</option>
              </select>
              <button
                type="button"
                class="text-red-500 hover:text-red-700"
                @click="form.skills.splice(idx, 1)"
              >
                ×
              </button>
            </div>
          </div>
          <BaseButton
            type="primary"
            icon="plus-box"
            size="small"
            :label="t('Add skill requirement')"
            @click="form.skills.push({ id: null, skill: '', level: null })"
          />
        </div>
      </div>
      <template #footer>
        <div class="flex gap-2 justify-end">
          <BaseButton type="plain" :label="t('Cancel')" @click="dialogVisible = false" />
          <BaseButton type="success" :label="t('Save')" :disabled="isSaving" @click="save" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import Dialog from "primevue/dialog"
import Select from "primevue/select"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import axios from "axios"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()

const items = ref([])
const professionalFunctions = ref([])
const businessUnits = ref([])
const geographicZones = ref([])
const activities = ref([])
const skills = ref([])
const levels = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const dialogVisible = ref(false)
const editingItem = ref(null)

const form = ref({
  title: "", description: "", professionalFunction: "", businessUnit: "",
  geographicZone: null, activities: [], skills: [],
})

const availableActivities = computed(() =>
  activities.value.filter((a) => !form.value.activities.includes(a["@id"])),
)

const activityLabel = (iri) => activities.value.find((a) => a["@id"] === iri)?.title ?? iri

async function load() {
  isLoading.value = true
  try {
    const [itemsRes, pfRes, buRes, gzRes, actRes, skillRes, levelRes] = await Promise.all([
      axios.get("/api/function_in_units?pagination=false"),
      axios.get("/api/professional_functions?pagination=false"),
      axios.get("/api/business_units?pagination=false"),
      axios.get("/api/geographic_zones?pagination=false"),
      axios.get("/api/activities?pagination=false"),
      axios.get("/hr/skills-data"),
      axios.get("/hr/levels-data"),
    ])
    items.value = itemsRes.data["hydra:member"] ?? []
    professionalFunctions.value = pfRes.data["hydra:member"] ?? []
    businessUnits.value = buRes.data["hydra:member"] ?? []
    geographicZones.value = gzRes.data["hydra:member"] ?? []
    activities.value = actRes.data["hydra:member"] ?? []
    skills.value = (skillRes.data["hydra:member"] ?? []).map((s) => ({ "@id": s["@id"], title: s.title }))
    levels.value = levelRes.data["hydra:member"] ?? []
  } finally {
    isLoading.value = false
  }
}

function openCreate() {
  editingItem.value = null
  form.value = {
    title: "", description: "", professionalFunction: "", businessUnit: "",
    geographicZone: null, activities: [], skills: [],
  }
  dialogVisible.value = true
}

async function openEdit(item) {
  editingItem.value = item
  let skillData = []
  if (item["@id"]) {
    const skillRes = await axios.get(`/api/function_in_unit_skills?functionInUnit=${encodeURIComponent(item["@id"])}&pagination=false`)
    skillData = (skillRes.data["hydra:member"] ?? []).map((s) => ({
      id: s["@id"],
      skill: s.skill ? (s.skill["@id"] ?? s.skill) : "",
      level: s.level ? (s.level["@id"] ?? s.level) : null,
    }))
  }
  form.value = {
    title: item.title,
    description: item.description ?? "",
    professionalFunction: item.professionalFunction ? (item.professionalFunction["@id"] ?? item.professionalFunction) : "",
    businessUnit: item.businessUnit ? (item.businessUnit["@id"] ?? item.businessUnit) : "",
    geographicZone: item.geographicZone ? (item.geographicZone["@id"] ?? item.geographicZone) : null,
    activities: (item.activities ?? []).map((a) => (typeof a === "string" ? a : a["@id"])),
    skills: skillData,
  }
  dialogVisible.value = true
}

function addActivity(event) {
  const iri = event.target.value
  if (iri && !form.value.activities.includes(iri)) form.value.activities.push(iri)
  event.target.value = ""
}

function removeActivity(iri) {
  form.value.activities = form.value.activities.filter((a) => a !== iri)
}

async function save() {
  if (!form.value.title.trim() || !form.value.professionalFunction || !form.value.businessUnit) return
  isSaving.value = true
  try {
    const payload = {
      title: form.value.title,
      description: form.value.description || null,
      professionalFunction: form.value.professionalFunction,
      businessUnit: form.value.businessUnit,
      geographicZone: form.value.geographicZone || null,
      activities: form.value.activities,
    }
    let fiuIri
    if (editingItem.value) {
      await axios.put(editingItem.value["@id"], payload)
      fiuIri = editingItem.value["@id"]
    } else {
      const res = await axios.post("/api/function_in_units", payload)
      fiuIri = res.data["@id"]
    }

    // Sync skill requirements
    const existing = form.value.skills.filter((s) => s.id)
    const existingIds = existing.map((s) => s.id)
    const skillRes = await axios.get(`/api/function_in_unit_skills?functionInUnit=${encodeURIComponent(fiuIri)}&pagination=false`)
    const serverSkills = skillRes.data["hydra:member"] ?? []
    for (const ss of serverSkills) {
      if (!existingIds.includes(ss["@id"])) {
        await axios.delete(ss["@id"])
      }
    }
    for (const sk of form.value.skills) {
      if (!sk.skill) continue
      if (sk.id) {
        await axios.put(sk.id, { functionInUnit: fiuIri, skill: sk.skill, level: sk.level || null })
      } else {
        await axios.post("/api/function_in_unit_skills", { functionInUnit: fiuIri, skill: sk.skill, level: sk.level || null })
      }
    }

    dialogVisible.value = false
    await load()
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      await axios.delete(item["@id"])
      await load()
    },
  })
}

onMounted(load)
</script>
