<template>
  <div class="p-4">
    <SectionHeader :title="t('Function-unit associations')">
      <BaseButton
        :label="t('Add association')"
        icon="plus-box"
        type="success"
        @click="openCreate"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="isLoading"
      :values="items"
    >
      <Column
        :header="t('Title')"
        field="title"
        sortable
      />
      <Column
        :header="t('Professional function')"
        field="professionalFunctionTitle"
        sortable
      />
      <Column
        :header="t('Business unit')"
        field="businessUnitTitle"
        sortable
      />
      <Column
        :header="t('Geographic zone')"
        field="geographicZoneTitle"
      />
      <Column
        :header="t('Actions')"
        style="width: 100px"
      >
        <template #body="{ data }">
          <div class="flex gap-1">
            <BaseButton
              icon="pencil"
              only-icon
              size="small"
              type="secondary-text"
              @click="openEdit(data)"
            />
            <BaseButton
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="dialogVisible"
      :style="{ width: '560px' }"
      :title="editingItem ? t('Edit association') : t('Add association')"
    >
      <BaseInputText
        id="fiu-title"
        v-model="form.title"
        :label="t('Title')"
        name="fiu_title"
      />
      <BaseTextArea
        id="fiu-description"
        v-model="form.description"
        label="Description"
        name="fiu_description"
        rows="2"
      />
      <BaseSelect
        id="fiu-professional-function"
        v-model="form.professionalFunction"
        :hast-empty-value="true"
        :label="t('Professional function')"
        :options="professionalFunctionOptions"
        name="fiu_professional_function"
      />
      <BaseSelect
        id="fiu-business-unit"
        v-model="form.businessUnit"
        :hast-empty-value="true"
        :label="t('Business unit')"
        :options="businessUnitOptions"
        name="fiu_business_unit"
      />
      <BaseSelect
        id="fiu-geographic-zone"
        v-model="form.geographicZone"
        :label="t('Geographic zone')"
        :options="geographicZoneOptions"
        allow-cleared
        name="fiu_geographic_zone"
      />
      <Fieldset
        v-if="editingItem"
        :legend="t('Activities')"
      >
        <div class="flex flex-wrap gap-2 mb-3">
          <span
            v-for="act in form.activities"
            :key="act"
            class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded flex items-center gap-1"
          >
            {{ activityLabel(act) }}
            <button
              class="hover:text-red-500"
              type="button"
              @click="removeActivity(act)"
            >
              ×
            </button>
          </span>
        </div>
        <BaseSelect
          id="fiu-add-activity"
          v-model="selectedActivityToAdd"
          :label="t('Add activity')"
          :options="activityOptions"
          allow-cleared
          name="fiu_add_activity"
        />
      </Fieldset>
      <Fieldset
        v-if="editingItem"
        :legend="t('Required skills')"
      >
        <div class="space-y-2 mb-3">
          <div
            v-for="(sk, idx) in form.skills"
            :key="idx"
            class="flex gap-2 items-center"
          >
            <BaseSelect
              :id="`fiu-skill-${idx}`"
              v-model="sk.skill"
              :label="t('Skill')"
              :options="skillOptions"
              class="flex-1"
              name="fiu_skill"
            />
            <BaseSelect
              :id="`fiu-level-${idx}`"
              v-model="sk.level"
              :label="t('Level')"
              :options="levelOptions"
              allow-cleared
              class="flex-1"
              name="fiu_skill_level"
            />
            <BaseButton
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="form.skills.splice(idx, 1)"
            />
          </div>
        </div>
        <BaseButton
          :label="t('Add skill requirement')"
          icon="plus-box"
          size="small"
          type="success"
          @click="form.skills.push({ id: null, skill: null, level: null })"
        />
      </Fieldset>
      <template #footer>
        <BaseButton
          :disabled="isSaving"
          :label="t('Save')"
          icon="content-save"
          type="success"
          @click="save"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Fieldset from "primevue/fieldset"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import * as functionInUnitService from "../../services/hr/functionInUnitService"
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
const selectedActivityToAdd = ref(null)

const form = ref({
  title: "",
  description: "",
  professionalFunction: null,
  businessUnit: null,
  geographicZone: null,
  activities: [],
  skills: [],
})

const availableActivities = computed(() => activities.value.filter((a) => !form.value.activities.includes(a["@id"])))

const activityLabel = (iri) => activities.value.find((a) => a["@id"] === iri)?.title ?? iri

const professionalFunctionOptions = computed(() =>
  professionalFunctions.value.map((fn) => ({ label: fn.title, value: fn["@id"] })),
)

const businessUnitOptions = computed(() => businessUnits.value.map((bu) => ({ label: bu.title, value: bu["@id"] })))

const geographicZoneOptions = computed(() => geographicZones.value.map((gz) => ({ label: gz.title, value: gz["@id"] })))

const activityOptions = computed(() => availableActivities.value.map((a) => ({ label: a.title, value: a["@id"] })))

const skillOptions = computed(() => skills.value.map((s) => ({ label: s.title, value: s["@id"] })))

const levelOptions = computed(() => levels.value.map((lv) => ({ label: lv.title, value: lv["@id"] })))

watch(selectedActivityToAdd, (iri) => {
  if (iri) {
    if (!form.value.activities.includes(iri)) {
      form.value.activities.push(iri)
    }
    selectedActivityToAdd.value = null
  }
})

async function load() {
  isLoading.value = true
  try {
    const [itemsList, pfRes, buRes, gzRes, actRes, skillRes, levelRes] = await Promise.all([
      functionInUnitService.getAll(),
      axios.get("/api/professional_functions?pagination=false"),
      axios.get("/api/business_units?pagination=false"),
      axios.get("/api/geographic_zones?pagination=false"),
      axios.get("/api/activities?pagination=false"),
      axios.get("/hr/skills-data"),
      axios.get("/hr/levels-data"),
    ])
    items.value = itemsList
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
    title: "",
    description: "",
    professionalFunction: null,
    businessUnit: null,
    geographicZone: null,
    activities: [],
    skills: [],
  }
  dialogVisible.value = true
}

async function openEdit(item) {
  editingItem.value = item
  let skillData = []
  if (item["@id"]) {
    const skillRes = await axios.get(
      `/api/function_in_unit_skills?functionInUnit=${encodeURIComponent(item["@id"])}&pagination=false`,
    )
    skillData = (skillRes.data["hydra:member"] ?? []).map((s) => ({
      id: s["@id"],
      skill: s.skill ? (s.skill["@id"] ?? s.skill) : null,
      level: s.level ? (s.level["@id"] ?? s.level) : null,
    }))
  }
  form.value = {
    title: item.title,
    description: item.description ?? "",
    professionalFunction: item.professionalFunction
      ? (item.professionalFunction["@id"] ?? item.professionalFunction)
      : null,
    businessUnit: item.businessUnit ? (item.businessUnit["@id"] ?? item.businessUnit) : null,
    geographicZone: item.geographicZone ? (item.geographicZone["@id"] ?? item.geographicZone) : null,
    activities: (item.activities ?? []).map((a) => (typeof a === "string" ? a : a["@id"])),
    skills: skillData,
  }
  dialogVisible.value = true
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
      await functionInUnitService.update(editingItem.value["@id"], payload)
      fiuIri = editingItem.value["@id"]
    } else {
      const created = await functionInUnitService.create(payload)
      fiuIri = created["@id"]
    }

    const existing = form.value.skills.filter((s) => s.id)
    const existingIds = existing.map((s) => s.id)
    const skillRes = await axios.get(
      `/api/function_in_unit_skills?functionInUnit=${encodeURIComponent(fiuIri)}&pagination=false`,
    )
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
        await axios.post("/api/function_in_unit_skills", {
          functionInUnit: fiuIri,
          skill: sk.skill,
          level: sk.level || null,
        })
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
      await functionInUnitService.remove(item["@id"])
      await load()
    },
  })
}

onMounted(load)
</script>
