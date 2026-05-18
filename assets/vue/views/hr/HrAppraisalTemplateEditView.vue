<template>
  <div class="space-y-6 max-w-4xl">
    <SectionHeader :title="isEdit ? t('Edit evaluation template') : t('Add evaluation template')">
      <BaseButton
        :label="t('Back to list')"
        :route="{ name: 'HrAppraisalTemplates' }"
        icon="arrow-left"
        type="plain"
      />
    </SectionHeader>

    <div
      v-if="loading"
      class="text-center py-8 text-gray-400"
    >
      {{ t("Loading…") }}
    </div>

    <form
      v-else
      class="space-y-6"
      @submit.prevent="save"
    >
      <!-- Basic info -->
      <div class="bg-white rounded border border-gray-200 p-6 space-y-4">
        <h3 class="font-semibold text-gray-700">
          {{ t("General information") }}
        </h3>
        <BaseInputText
          id="template-title"
          v-model="form.title"
          :label="t('Title')"
          name="title"
          required
        />
        <BaseSelect
          id="template-periodicity"
          v-model="form.periodicity"
          :label="t('Periodicity')"
          :options="periodicityOptions"
          allow-clear
          name="periodicity"
        />
      </div>

      <!-- Weight summary -->
      <div
        :class="
          totalWeight !== 100 && form.items.length > 0
            ? 'bg-red-50 border-red-300 text-red-700'
            : 'bg-green-50 border-green-300 text-green-700'
        "
        class="rounded border px-4 py-2 text-sm font-medium"
      >
        {{ t("Total weight") }}: {{ totalWeight }}%
        <span v-if="form.items.length > 0 && totalWeight !== 100"> — {{ t("Must equal 100%") }} </span>
      </div>

      <!-- Skills section -->
      <div class="bg-white rounded border border-gray-200 p-6 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">
            {{ t("Skills") }}
          </h3>
          <BaseButton
            :label="t('Add skill')"
            icon="plus"
            size="small"
            type="success"
            @click="addItem('skill')"
          />
        </div>
        <table
          v-if="skillItems.length"
          class="w-full text-sm"
        >
          <thead>
            <tr class="text-left text-gray-500 border-b">
              <th class="pb-2 pr-4">
                {{ t("Skill") }}
              </th>
              <th class="pb-2 pr-4 w-28">
                {{ t("Weight %") }}
              </th>
              <th class="pb-2 w-10" />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, idx) in skillItems"
              :key="idx"
              class="border-b last:border-0"
            >
              <td class="py-2 pr-4">
                <BaseAutocomplete
                  :id="'skill_ref_' + idx"
                  v-model="item.ref"
                  :label="t('Skill')"
                  :search="skillsCache.search"
                  option-label="title"
                />
              </td>
              <td class="py-2 pr-4">
                <BaseInputNumber
                  :id="'skill_pct_' + idx"
                  v-model="item.percentage"
                  :label="t('Weight %')"
                  :max="100"
                  :min="0"
                />
              </td>
              <td class="py-2">
                <BaseButton
                  :label="t('Remove')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="removeItem(item)"
                />
              </td>
            </tr>
          </tbody>
        </table>
        <p
          v-else
          class="text-sm text-gray-400"
        >
          {{ t("No skills added yet.") }}
        </p>
      </div>

      <!-- Activities section -->
      <div class="bg-white rounded border border-gray-200 p-6 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">
            {{ t("Activities") }}
          </h3>
          <BaseButton
            :label="t('Add activity')"
            icon="plus"
            size="small"
            type="success"
            @click="addItem('activity')"
          />
        </div>
        <table
          v-if="activityItems.length"
          class="w-full text-sm"
        >
          <thead>
            <tr class="text-left text-gray-500 border-b">
              <th class="pb-2 pr-4">
                {{ t("Activity") }}
              </th>
              <th class="pb-2 pr-4 w-28">
                {{ t("Weight %") }}
              </th>
              <th class="pb-2 w-10" />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, idx) in activityItems"
              :key="idx"
              class="border-b last:border-0"
            >
              <td class="py-2 pr-4">
                <BaseAutocomplete
                  :id="'activity_ref_' + idx"
                  v-model="item.ref"
                  :label="t('Activity')"
                  :search="activitiesCache.search"
                  option-label="title"
                />
              </td>
              <td class="py-2 pr-4">
                <BaseInputNumber
                  :id="'activity_pct_' + idx"
                  v-model="item.percentage"
                  :label="t('Weight %')"
                  :max="100"
                  :min="0"
                />
              </td>
              <td class="py-2">
                <BaseButton
                  :label="t('Remove')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="removeItem(item)"
                />
              </td>
            </tr>
          </tbody>
        </table>
        <p
          v-else
          class="text-sm text-gray-400"
        >
          {{ t("No activities added yet.") }}
        </p>
      </div>

      <!-- Objectives section -->
      <div class="bg-white rounded border border-gray-200 p-6 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-700">
            {{ t("Objectives") }}
          </h3>
          <BaseButton
            :label="t('Add objective')"
            icon="plus"
            size="small"
            type="success"
            @click="addItem('objective')"
          />
        </div>
        <table
          v-if="objectiveItems.length"
          class="w-full text-sm"
        >
          <thead>
            <tr class="text-left text-gray-500 border-b">
              <th class="pb-2 pr-4">
                {{ t("Objective") }}
              </th>
              <th class="pb-2 pr-4 w-28">
                {{ t("Weight %") }}
              </th>
              <th class="pb-2 w-10" />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, idx) in objectiveItems"
              :key="idx"
              class="border-b last:border-0"
            >
              <td class="py-2 pr-4">
                <BaseAutocomplete
                  :id="'objective_ref_' + idx"
                  v-model="item.ref"
                  :label="t('Objective')"
                  :search="objectivesCache.search"
                  option-label="title"
                />
              </td>
              <td class="py-2 pr-4">
                <BaseInputNumber
                  :id="'objective_pct_' + idx"
                  v-model="item.percentage"
                  :label="t('Weight %')"
                  :max="100"
                  :min="0"
                />
              </td>
              <td class="py-2">
                <BaseButton
                  :label="t('Remove')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="removeItem(item)"
                />
              </td>
            </tr>
          </tbody>
        </table>
        <p
          v-else
          class="text-sm text-gray-400"
        >
          {{ t("No objectives added yet.") }}
        </p>
      </div>

      <div class="flex justify-end gap-3">
        <BaseButton
          :label="t('Cancel')"
          :route="{ name: 'HrAppraisalTemplates' }"
          icon="close"
          type="plain"
        />
        <BaseButton
          :disabled="form.items.length > 0 && totalWeight !== 100"
          :label="t('Save')"
          :loading="saving"
          icon="content-save"
          type="success"
          @click="save"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter, useRoute } from "vue-router"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useEntityCache } from "../../composables/useEntityCache"
import { useNotification } from "../../composables/notification"
import baseService from "../../services/baseService"
import * as periodicityService from "../../services/hr/periodicityService"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { showSuccessNotification, showErrorNotification } = useNotification()

const isEdit = computed(() => !!route.params.id)

const loading = ref(false)
const saving = ref(false)
const periodicities = ref([])

const skillsCache = useEntityCache("/api/skills", { pagination: false })
const activitiesCache = useEntityCache("/api/activities", { pagination: false })
const objectivesCache = useEntityCache("/api/performance_objectives", { pagination: false })

const cacheByType = {
  skill: skillsCache,
  activity: activitiesCache,
  objective: objectivesCache,
}

const form = ref({ title: "", periodicity: "", items: [] })

const skillItems = computed(() => form.value.items.filter((i) => i.type === "skill"))
const activityItems = computed(() => form.value.items.filter((i) => i.type === "activity"))
const objectiveItems = computed(() => form.value.items.filter((i) => i.type === "objective"))
const totalWeight = computed(() => form.value.items.reduce((sum, i) => sum + (Number(i.percentage) || 0), 0))

const periodicityOptions = computed(() =>
  periodicities.value.map((p) => ({
    label: `${p.title} (${p.days} ${t("days")})`,
    value: p["@id"],
  })),
)

function addItem(type) {
  form.value.items.push({ type, ref: null, percentage: 0, _key: Date.now() + Math.random() })
}

function removeItem(item) {
  const idx = form.value.items.indexOf(item)
  if (idx !== -1) form.value.items.splice(idx, 1)
}

async function load() {
  loading.value = true
  try {
    const [perioItems] = await Promise.all([
      periodicityService.getAll(),
      skillsCache.load(),
      activitiesCache.load(),
      objectivesCache.load(),
    ])
    periodicities.value = perioItems

    if (isEdit.value) {
      const tpl = await baseService.get(`/api/performance_appraisal_templates/${route.params.id}`)
      form.value = {
        title: tpl.title,
        periodicity: tpl.periodicity ? tpl.periodicity["@id"] : "",
        items: (tpl.items ?? []).map((i) => ({
          type: i.type,
          ref: cacheByType[i.type]?.findById(i.ref) ?? null,
          percentage: i.percentage,
          _key: i.id,
        })),
      }
    }
  } catch {
    showErrorNotification(t("Could not load data"))
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!form.value.title) return
  saving.value = true
  try {
    const payload = {
      title: form.value.title,
      periodicity: form.value.periodicity || null,
      items: form.value.items
        .filter((i) => i.ref?.id && i.type)
        .map((i) => ({ type: i.type, ref: Number(i.ref.id), percentage: Number(i.percentage) })),
    }
    if (isEdit.value) {
      await baseService.put(`/api/performance_appraisal_templates/${route.params.id}`, payload)
    } else {
      await baseService.post("/api/performance_appraisal_templates", payload)
    }
    showSuccessNotification(t("Template saved"))

    await router.push({ name: "HrAppraisalTemplates" })
  } catch {
    showErrorNotification(t("Could not save template"))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
