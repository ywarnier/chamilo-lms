<template>
  <div class="flex gap-6 p-4 min-h-screen">
    <!-- Left sidebar: SDG list (one entry per SDG number) -->
    <aside class="w-64 flex-shrink-0">
      <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
        {{ t("SDG Goals") }}
      </h2>
      <ul class="space-y-1">
        <li
          v-for="sdgNum in uniqueSdgNumbers"
          :key="sdgNum"
        >
          <button
            class="w-full text-left px-3 py-2 rounded text-sm flex items-center gap-2 transition-colors"
            :class="
              selectedSdgNumber === sdgNum
                ? 'bg-blue-100 text-blue-800 font-medium'
                : 'text-gray-700 hover:bg-gray-100'
            "
            @click="selectSdg(sdgNum)"
          >
            <img
              :src="`/img/sdg_icons/goal_${sdgNum}.png`"
              :alt="`SDG ${sdgNum}`"
              class="w-6 h-6 object-contain flex-shrink-0"
            >
            <span class="truncate">{{ sdgNum }}. {{ labelForSdg(sdgNum) }}</span>
            <span
              v-if="isAnySdgPublished(sdgNum)"
              class="ml-auto inline-block w-2 h-2 rounded-full bg-green-500 flex-shrink-0"
              :title="t('Published')"
            />
          </button>
        </li>
      </ul>
    </aside>

    <!-- Main panel -->
    <main class="flex-1 max-w-3xl">
      <div
        v-if="isLoading"
        class="flex justify-center py-16 text-gray-400"
      >
        {{ t("Loading") }}…
      </div>

      <div v-else-if="selected">
        <!-- Header -->
        <div class="flex items-center gap-4 mb-6">
          <img
            :src="`/img/sdg_icons/goal_${selected.sdgNumber}.png`"
            :alt="`SDG ${selected.sdgNumber}`"
            class="w-16 h-16 object-contain"
          >
          <div class="flex-1">
            <h1 class="text-xl font-bold text-gray-800">
              {{ selected.sdgNumber }}. {{ selected.title }}
            </h1>
            <!-- Language tabs -->
            <div class="flex items-center gap-2 mt-2 flex-wrap">
              <button
                v-for="lang in sdgLanguages"
                :key="lang"
                class="text-xs px-2 py-0.5 rounded border transition-colors"
                :class="
                  activeLanguage === lang
                    ? 'border-blue-500 bg-blue-50 text-blue-700 font-bold'
                    : 'border-gray-300 text-gray-500 hover:border-gray-400 hover:text-gray-700'
                "
                @click="switchLanguage(lang)"
              >
                {{ languageLabel(lang) }}
              </button>
              <!-- Translate button -->
              <BaseButton
                type="primary"
                icon="translate"
                size="small"
                :label="t('Add translation')"
                :title="aiAvailable ? t('Translate this goal to another language') : t('Add a manual translation')"
                @click="openTranslateDialog"
              />
            </div>
          </div>
        </div>

        <!-- Edit form -->
        <form
          class="space-y-4"
          @submit.prevent="save"
        >
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t("Title") }}
            </label>
            <input
              v-model="form.title"
              name="title"
              type="text"
              class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t("Description") }}
            </label>
            <textarea
              v-model="form.description"
              name="description"
              rows="5"
              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              {{ t("Enforcement") }}
            </label>
            <textarea
              v-model="form.enforcement"
              name="enforcement"
              rows="5"
              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
            />
          </div>

          <div class="flex items-center gap-2">
            <input
              id="is-published"
              v-model="form.isPublished"
              name="isPublished"
              type="checkbox"
              class="rounded border-gray-300"
            >
            <label
              for="is-published"
              class="text-sm text-gray-700"
            >
              {{ t("Published") }}
            </label>
          </div>

          <div class="flex gap-3 pt-2">
            <BaseButton
              type="success"
              icon="content-save"
              :label="t('Save')"
              :loading="isSaving"
              is-submit
            />
          </div>
        </form>

        <!-- History section -->
        <div class="mt-8">
          <button
            class="text-sm text-blue-600 hover:underline flex items-center gap-1"
            @click="toggleHistory"
          >
            <BaseIcon :icon="showHistory ? 'chevron-up' : 'chevron-down'" />
            {{ t("Change history") }}
            <span
              v-if="history.length"
              class="text-gray-400"
            >({{ history.length }})</span>
          </button>

          <div
            v-if="showHistory"
            class="mt-3 border border-gray-200 rounded divide-y divide-gray-100 text-sm"
          >
            <div
              v-if="history.length === 0"
              class="px-4 py-3 text-gray-400"
            >
              {{ t("No history yet.") }}
            </div>
            <div
              v-for="entry in history"
              :key="entry.id"
              class="px-4 py-3"
            >
              <div class="flex items-center justify-between mb-1">
                <span class="text-gray-500">{{ formatDate(entry.changedAt) }}</span>
                <span class="text-gray-400 text-xs">{{ entry.changedBy ?? t("Unknown") }}</span>
                <span
                  class="text-xs px-2 py-0.5 rounded"
                  :class="entry.isPublished ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                >{{ entry.isPublished ? t("Published") : t("Unpublished") }}</span>
              </div>
              <p class="text-gray-600 line-clamp-2">{{ entry.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <div
        v-else
        class="text-gray-400 text-center py-16"
      >
        {{ t("Select a goal from the sidebar.") }}
      </div>
    </main>
  </div>

  <!-- Translate dialog -->
  <div
    v-if="translateDialog.open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
  >
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
      <h2 class="text-lg font-semibold mb-4">
        {{ t("Translate SDG {0}", [selected?.sdgNumber]) }}
      </h2>

      <div
        v-if="!translateDialog.done"
        class="space-y-4"
      >
        <p class="text-sm text-gray-600">
          {{ t("Translate the description and enforcement from") }}
          <strong>{{ languageLabel(selected?.language) }}</strong>
          {{ t("to:") }}
        </p>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Target language") }}</label>
          <select
            v-model="translateDialog.targetLanguage"
            name="targetLanguage"
            class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm"
          >
            <option
              value=""
              disabled
            >
              {{ t("— select a language —") }}
            </option>
            <option
              v-for="lang in availableTargetLanguages"
              :key="lang.isocode"
              :value="lang.isocode"
            >
              {{ lang.englishName }}
            </option>
          </select>
        </div>

        <div class="flex gap-3 justify-end">
          <BaseButton
            type="plain"
            :label="t('Cancel')"
            @click="translateDialog.open = false"
          />
          <BaseButton
            v-if="aiAvailable"
            type="primary"
            icon="translate"
            :label="t('Translate with AI')"
            :loading="translateDialog.loading"
            :disabled="!translateDialog.targetLanguage"
            @click="runTranslation"
          />
          <BaseButton
            type="secondary"
            icon="pencil"
            :label="t('Enter manually')"
            :disabled="!translateDialog.targetLanguage"
            @click="openManualEntry"
          />
        </div>
      </div>

      <div
        v-else
        class="space-y-4"
      >
        <p class="text-sm text-green-700 font-medium">
          {{ t("Translation ready — please review before saving.") }}
        </p>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Title") }}</label>
          <input
            v-model="translateDialog.title"
            type="text"
            class="w-full border border-gray-300 rounded px-3 py-1.5 text-sm"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Description") }}</label>
          <textarea
            v-model="translateDialog.description"
            rows="4"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t("Enforcement") }}</label>
          <textarea
            v-model="translateDialog.enforcement"
            rows="4"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
          />
        </div>

        <p class="text-xs text-gray-500">
          {{ t("This will be saved as a new {0} version of SDG {1}.", [languageLabel(translateDialog.targetLanguage), selected?.sdgNumber]) }}
        </p>

        <div class="flex gap-3 justify-end">
          <BaseButton
            type="plain"
            :label="t('Cancel')"
            @click="translateDialog.open = false"
          />
          <BaseButton
            type="success"
            icon="content-save"
            :label="t('Confirm and save')"
            :loading="translateDialog.saving"
            @click="confirmTranslation"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import axios from "axios"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import * as goalService from "../../services/hr/socialResponsibilityGoalService"

const { t } = useI18n()

const allGoals = ref([])
const isLoading = ref(true)
const isSaving = ref(false)
const showHistory = ref(false)
const history = ref([])
const aiAvailable = ref(false)
const platformLanguages = ref([])

const selectedSdgNumber = ref(null)
const activeLanguage = ref(null)

const form = reactive({
  title: "",
  description: "",
  enforcement: "",
  isPublished: false,
})

const translateDialog = reactive({
  open: false,
  loading: false,
  saving: false,
  done: false,
  targetLanguage: "",
  title: "",
  description: "",
  enforcement: "",
})

// Map: sdgNumber -> (language -> goal)
const goalsByNumber = computed(() => {
  const map = new Map()
  for (const goal of allGoals.value) {
    if (!map.has(goal.sdgNumber)) {
      map.set(goal.sdgNumber, new Map())
    }
    map.get(goal.sdgNumber).set(goal.language, goal)
  }
  return map
})

const uniqueSdgNumbers = computed(() => {
  return [...goalsByNumber.value.keys()].sort((a, b) => a - b)
})

// All languages available for the currently selected SDG
const sdgLanguages = computed(() => {
  if (selectedSdgNumber.value === null) return []
  const langMap = goalsByNumber.value.get(selectedSdgNumber.value)
  return langMap ? [...langMap.keys()] : []
})

// The currently viewed goal
const selected = computed(() => {
  if (selectedSdgNumber.value === null || activeLanguage.value === null) return null
  const langMap = goalsByNumber.value.get(selectedSdgNumber.value)
  return langMap?.get(activeLanguage.value) ?? null
})

// Languages not yet translated for the current SDG (for translate dialog)
const availableTargetLanguages = computed(() => {
  const already = new Set(sdgLanguages.value)
  return platformLanguages.value.filter((l) => !already.has(l.isocode))
})

// Language label helper (isocode → English name from platformLanguages, fallback to isocode)
function languageLabel(isocode) {
  if (!isocode) return ""
  const found = platformLanguages.value.find((l) => l.isocode === isocode)
  return found ? found.englishName : isocode
}

function labelForSdg(sdgNum) {
  const langMap = goalsByNumber.value.get(sdgNum)
  if (!langMap) return ""
  const preferred = activeLanguage.value && langMap.has(activeLanguage.value)
    ? langMap.get(activeLanguage.value)
    : [...langMap.values()][0]
  return preferred?.title ?? ""
}

function isAnySdgPublished(sdgNum) {
  const langMap = goalsByNumber.value.get(sdgNum)
  if (!langMap) return false
  return [...langMap.values()].some((g) => g.isPublished)
}

onMounted(async () => {
  try {
    const [goals, langsRes] = await Promise.all([
      goalService.getAll(),
      axios.get("/hr/social-responsibility/languages").catch(() => ({ data: [] })),
    ])
    allGoals.value = goals
    platformLanguages.value = langsRes.data ?? []

    // Default active language: prefer en_US, else first available
    const allLangs = [...new Set(allGoals.value.map((g) => g.language))]
    activeLanguage.value = allLangs.includes("en_US") ? "en_US" : allLangs[0] ?? null

    try {
      const ai = await axios.get("/ai/text_providers")
      aiAvailable.value = (ai.data?.providers ?? []).length > 0
    } catch {
      aiAvailable.value = false
    }
  } finally {
    isLoading.value = false
  }
})

function selectSdg(sdgNum) {
  selectedSdgNumber.value = sdgNum
  // Stay on current activeLanguage if available, else fall back to first
  const langMap = goalsByNumber.value.get(sdgNum)
  if (langMap && !langMap.has(activeLanguage.value)) {
    activeLanguage.value = [...langMap.keys()][0]
  }
  showHistory.value = false
  history.value = []
  syncForm()
}

function switchLanguage(lang) {
  activeLanguage.value = lang
  showHistory.value = false
  history.value = []
  syncForm()
}

function syncForm() {
  const goal = selected.value
  form.title = goal?.title ?? ""
  form.description = goal?.description ?? ""
  form.enforcement = goal?.enforcement ?? ""
  form.isPublished = goal?.isPublished ?? false
}

async function save() {
  if (!selected.value) return
  isSaving.value = true
  try {
    const updated = await goalService.update(selected.value["@id"], {
      title: form.title,
      description: form.description || null,
      enforcement: form.enforcement || null,
      isPublished: form.isPublished,
    })
    // Update in allGoals
    const idx = allGoals.value.findIndex((g) => g.id === selected.value.id)
    if (idx !== -1) {
      allGoals.value[idx] = { ...allGoals.value[idx], ...updated }
    }
  } finally {
    isSaving.value = false
  }
}

async function toggleHistory() {
  showHistory.value = !showHistory.value
  if (showHistory.value && history.value.length === 0 && selected.value) {
    try {
      const response = await axios.get(`/hr/social-responsibility/${selected.value.id}/history`)
      history.value = response.data
    } catch {
      history.value = []
    }
  }
}

function openTranslateDialog() {
  translateDialog.open = true
  translateDialog.done = false
  translateDialog.loading = false
  translateDialog.saving = false
  translateDialog.targetLanguage = ""
  translateDialog.title = ""
  translateDialog.description = ""
  translateDialog.enforcement = ""
}

function openManualEntry() {
  translateDialog.title = ""
  translateDialog.description = ""
  translateDialog.enforcement = ""
  translateDialog.done = true
}

async function runTranslation() {
  if (!selected.value || !translateDialog.targetLanguage) return
  translateDialog.loading = true
  try {
    const response = await axios.post("/hr/social-responsibility/translate", {
      goalId: selected.value.id,
      targetLanguage: translateDialog.targetLanguage,
    })
    translateDialog.title = response.data.title ?? ""
    translateDialog.description = response.data.description ?? ""
    translateDialog.enforcement = response.data.enforcement ?? ""
    translateDialog.done = true
  } catch (err) {
    alert(err.response?.data?.error ?? t("Translation failed."))
  } finally {
    translateDialog.loading = false
  }
}

async function confirmTranslation() {
  if (!selected.value) return
  translateDialog.saving = true
  try {
    const targetLang = translateDialog.targetLanguage
    const langMap = goalsByNumber.value.get(selected.value.sdgNumber)
    const existing = langMap?.get(targetLang)

    if (existing) {
      await goalService.update(existing["@id"], {
        title: translateDialog.title || existing.title,
        description: translateDialog.description || null,
        enforcement: translateDialog.enforcement || null,
        isPublished: false,
      })
    } else {
      await axios.post("/hr/social-responsibility/save-translation", {
        sdgNumber: selected.value.sdgNumber,
        language: targetLang,
        title: translateDialog.title || selected.value.title,
        description: translateDialog.description || null,
        enforcement: translateDialog.enforcement || null,
      })
    }

    // Refresh goal list and switch to new language
    allGoals.value = await goalService.getAll()
    activeLanguage.value = targetLang

    translateDialog.open = false
    syncForm()
  } finally {
    translateDialog.saving = false
  }
}

function formatDate(iso) {
  return new Date(iso).toLocaleString()
}
</script>
