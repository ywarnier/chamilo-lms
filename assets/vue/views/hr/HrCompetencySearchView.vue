<template>
  <div class="p-4">
    <SectionHeader :title="t('Competency search')" />
    <div class="flex gap-6">
      <!-- Left sidebar: mode selector + search form -->
      <div class="w-64 shrink-0">
        <div class="space-y-1 mb-6">
          <button
            v-for="m in modes"
            :key="m.key"
            :class="[
              'w-full text-left px-3 py-2 rounded text-sm',
              activeMode === m.key
                ? 'bg-blue-100 text-blue-700 font-semibold border-l-4 border-blue-600'
                : 'text-gray-700 hover:bg-gray-100 border-l-4 border-transparent',
            ]"
            @click="selectMode(m.key)"
          >
            {{ m.label }}
          </button>
        </div>

        <!-- Mode A: by skill -->
        <div
          v-if="activeMode === 'by_skill'"
          class="space-y-3"
        >
          <BaseMultiSelect
            v-model="searchA.skillIds"
            :label="t('Skill(s)')"
            :options="skillOptions"
            input-id="cs_skill_ids"
            option-label="label"
            option-value="value"
          />
          <BaseSelect
            id="cs-level"
            v-model="searchA.levelId"
            :label="t('Required level')"
            :options="levelOptions"
            allow-cleared
            name="cs_level_id"
          />
          <BaseButton
            :disabled="isSearching"
            :label="t('Search')"
            icon="search"
            type="primary"
            @click="search"
          />
          <div
            v-if="searchA.skillIds.length > 0"
            class="mt-2 p-2 bg-gray-50 rounded border border-gray-200 text-xs text-gray-600"
          >
            <div class="font-medium mb-1">{{ t("Selected skills") }}:</div>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="id in searchA.skillIds"
                :key="id"
                class="bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded"
                >{{ skillLabel(id) }}</span
              >
            </div>
            <div
              v-if="searchA.levelId"
              class="mt-1"
            >
              {{ t("Level") }}: {{ levelLabel(searchA.levelId) }}
            </div>
          </div>
        </div>

        <!-- Mode B: by function -->
        <div
          v-if="activeMode === 'by_function'"
          class="space-y-3"
        >
          <BaseSelect
            id="cs-function"
            v-model="searchB.functionId"
            :hast-empty-value="true"
            :label="t('Function-unit association')"
            :options="functionInUnitOptions"
            name="cs_function_in_unit"
          />
          <BaseButton
            :disabled="isSearching"
            :label="t('Search')"
            icon="search"
            type="primary"
            @click="search"
          />
          <div
            v-if="searchB.functionId"
            class="mt-2 p-2 bg-gray-50 rounded border border-gray-200 text-xs text-gray-600"
          >
            <div class="font-medium">{{ t("Function") }}:</div>
            <div class="mt-0.5 text-blue-700 mb-2">{{ functionLabel(searchB.functionId) }}</div>
            <template v-if="selectedFunctionSkills.length > 0">
              <div class="font-medium mb-1">{{ t("Required skills") }}:</div>
              <div class="flex flex-col gap-1">
                <div
                  v-for="s in selectedFunctionSkills"
                  :key="s.id"
                  class="flex items-center gap-1 flex-wrap"
                >
                  <span class="bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">{{ s.skillTitle }}</span>
                  <span
                    v-if="s.levelTitle"
                    class="text-gray-500"
                    >{{ s.levelTitle }}</span
                  >
                </div>
              </div>
            </template>
            <div
              v-else
              class="text-gray-400 italic"
            >
              {{ t("No required skills defined") }}
            </div>
          </div>
        </div>

        <!-- Mode C: compare users -->
        <div
          v-if="activeMode === 'compare_users'"
          class="space-y-3"
        >
          <BaseSelect
            id="cs-user-a"
            v-model="searchC.userAId"
            :hast-empty-value="true"
            :label="t('User A')"
            :options="userOptions"
            name="cs_user_a"
          />
          <BaseSelect
            id="cs-user-b"
            v-model="searchC.userBId"
            :hast-empty-value="true"
            :label="t('User B')"
            :options="userOptions"
            name="cs_user_b"
          />
          <BaseButton
            :disabled="isSearching"
            :label="t('Compare')"
            icon="search"
            type="primary"
            @click="search"
          />
        </div>

        <!-- Mode D: user vs function -->
        <div
          v-if="activeMode === 'user_vs_function'"
          class="space-y-3"
        >
          <BaseSelect
            id="cs-user-vs"
            v-model="searchD.userId"
            :hast-empty-value="true"
            :label="t('Staff member')"
            :options="userOptions"
            name="cs_user_id"
          />
          <BaseSelect
            id="cs-function-vs"
            v-model="searchD.functionId"
            :hast-empty-value="true"
            :label="t('Function-unit association')"
            :options="functionInUnitOptions"
            name="cs_function_vs"
          />
          <BaseButton
            :disabled="isSearching"
            :label="t('Compare')"
            icon="search"
            type="primary"
            @click="search"
          />
        </div>
      </div>

      <!-- Results panel -->
      <div class="flex-1">
        <div
          v-if="isSearching"
          class="text-gray-500"
        >
          {{ t("Searching…") }}
        </div>

        <!-- Mode A & B results: staff list -->
        <template v-if="results && (activeMode === 'by_skill' || activeMode === 'by_function')">
          <p class="text-sm text-gray-600 mb-3">{{ results.length }} {{ t("staff member(s) found") }}</p>
          <BaseTable
            :is-loading="false"
            :values="results"
          >
            <Column
              :header="t('First name')"
              field="firstname"
            />
            <Column
              :header="t('Last name')"
              field="lastname"
              sortable
            />
            <Column
              :header="t('Username')"
              field="username"
            />
          </BaseTable>
        </template>

        <!-- Mode C: side-by-side comparison -->
        <template v-if="results && activeMode === 'compare_users'">
          <div class="grid grid-cols-3 text-sm font-medium border-b border-gray-300 mb-1 pb-1">
            <div>{{ t("Skill") }}</div>
            <div class="text-center">{{ results.userA?.fullName }}</div>
            <div class="text-center">{{ results.userB?.fullName }}</div>
          </div>
          <div
            v-for="row in results.rows"
            :key="row.skillId"
            class="grid grid-cols-3 text-sm py-1 border-b border-gray-100"
          >
            <div>{{ row.skillTitle }}</div>
            <div class="text-center text-gray-700">{{ row.userA?.levelTitle ?? "—" }}</div>
            <div class="text-center text-gray-700">{{ row.userB?.levelTitle ?? "—" }}</div>
          </div>
        </template>

        <!-- Mode D: user vs function -->
        <template v-if="results && activeMode === 'user_vs_function'">
          <div class="text-sm mb-3">
            <span class="font-medium">{{ results.user?.fullName }}</span>
            {{ t("vs.") }}
            <span class="font-medium">{{ results.functionInUnit?.title }}</span>
          </div>
          <div class="grid grid-cols-4 text-xs font-medium text-gray-500 border-b border-gray-300 pb-1 mb-1">
            <div>{{ t("Skill") }}</div>
            <div class="text-center">{{ t("Required level") }}</div>
            <div class="text-center">{{ t("Acquired level") }}</div>
            <div class="text-center">{{ t("Status") }}</div>
          </div>
          <div
            v-for="row in results.rows"
            :key="row.skillId"
            class="grid grid-cols-4 text-sm py-1 border-b border-gray-100 items-center"
          >
            <div>{{ row.skillTitle }}</div>
            <div class="text-center text-gray-600">{{ row.requiredLevelTitle ?? "—" }}</div>
            <div class="text-center text-gray-600">{{ row.acquiredLevelTitle ?? "—" }}</div>
            <div class="text-center">
              <span
                :class="{
                  'bg-green-100 text-green-700': row.status === 'ok',
                  'bg-orange-100 text-orange-700': row.status === 'below',
                  'bg-red-100 text-red-700': row.status === 'missing',
                }"
                class="px-2 py-0.5 rounded text-xs font-medium"
              >
                {{ statusLabel(row.status) }}
              </span>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import * as functionInUnitService from "../../services/hr/functionInUnitService"
import axios from "axios"

const { t } = useI18n()

const modes = [
  { key: "by_skill", label: "a) " + "Search by skill" },
  { key: "by_function", label: "b) " + "Search by function profile" },
  { key: "compare_users", label: "c) " + "Compare two staff members" },
  { key: "user_vs_function", label: "d) " + "User vs. function profile" },
]

const activeMode = ref("by_skill")
const isSearching = ref(false)
const results = ref(null)

const skills = ref([])
const levels = ref([])
const functionInUnits = ref([])
const users = ref([])

const searchA = ref({ skillIds: [], levelId: null })
const searchB = ref({ functionId: null })
const searchC = ref({ userAId: null, userBId: null })
const searchD = ref({ userId: null, functionId: null })

const selectedFunctionSkills = ref([])

const skillOptions = computed(() => skills.value.map((sk) => ({ label: sk.title, value: sk.id })))
const levelOptions = computed(() => levels.value.map((lv) => ({ label: lv.title, value: lv.id })))
const functionInUnitOptions = computed(() => functionInUnits.value.map((f) => ({ label: f.title, value: f.id })))
const userOptions = computed(() => users.value.map((u) => ({ label: u.fullName, value: u.id })))

watch(
  () => searchB.value.functionId,
  async (id) => {
    selectedFunctionSkills.value = []
    if (!id) return
    const res = await axios.get(`/hr/function-skills-data/${id}`)
    selectedFunctionSkills.value = res.data ?? []
  },
)

function skillLabel(id) {
  return skills.value.find((s) => s.id == id)?.title ?? id
}

function levelLabel(id) {
  return levels.value.find((l) => l.id == id)?.title ?? id
}

function functionLabel(id) {
  return functionInUnits.value.find((f) => f.id == id)?.title ?? id
}

function selectMode(key) {
  activeMode.value = key
  results.value = null
}

function statusLabel(status) {
  if ("ok" === status) return t("Meets level")
  if ("below" === status) return t("Below required")
  return t("Missing")
}

async function search() {
  isSearching.value = true
  results.value = null
  try {
    let params = { mode: activeMode.value }
    if ("by_skill" === activeMode.value) {
      params.skill_ids = searchA.value.skillIds
      params.level_id = searchA.value.levelId
    } else if ("by_function" === activeMode.value) {
      params.function_in_unit_id = searchB.value.functionId
    } else if ("compare_users" === activeMode.value) {
      params.user_a = searchC.value.userAId
      params.user_b = searchC.value.userBId
    } else {
      params.user_id = searchD.value.userId
      params.function_in_unit_id = searchD.value.functionId
    }
    const res = await axios.get("/hr/competency-search-data", { params })
    results.value = res.data
  } finally {
    isSearching.value = false
  }
}

async function load() {
  const [skillRes, levelRes, fiuList, userRes] = await Promise.all([
    axios.get("/hr/skills-data"),
    axios.get("/hr/levels-data"),
    functionInUnitService.getAll(),
    axios.get("/api/users?pagination=false&properties[]=id&properties[]=fullName"),
  ])
  skills.value = (skillRes.data["hydra:member"] ?? []).map((s) => ({
    id: s.id ?? s["@id"].split("/").pop(),
    title: s.title,
  }))
  levels.value = (levelRes.data["hydra:member"] ?? []).map((l) => ({
    id: l.id ?? l["@id"].split("/").pop(),
    title: l.title,
  }))
  functionInUnits.value = fiuList.map((f) => ({
    id: f["@id"].split("/").pop(),
    title: f.title,
  }))
  users.value = (userRes.data["hydra:member"] ?? []).map((u) => ({
    id: u.id ?? u["@id"].split("/").pop(),
    fullName: u.fullName,
  }))
}

onMounted(load)
</script>
