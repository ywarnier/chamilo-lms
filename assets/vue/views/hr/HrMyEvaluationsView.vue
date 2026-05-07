<template>
  <div>
    <SectionHeader :title="t('My evaluations')" />

    <div class="mb-6 border-b border-gray-200">
      <nav class="flex gap-6">
        <button
          :class="activeTab === 'evaluatee' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
          class="py-3 text-sm font-medium focus:outline-none"
          @click="switchTab('evaluatee')"
        >
          {{ t("Evaluations of me") }}
          <span
            v-if="evaluationsOfMe.length"
            class="ml-1 bg-blue-100 text-blue-700 text-xs rounded-full px-2"
          >{{ evaluationsOfMe.length }}</span>
        </button>
        <button
          :class="activeTab === 'evaluator' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
          class="py-3 text-sm font-medium focus:outline-none"
          @click="switchTab('evaluator')"
        >
          {{ t("Evaluations I must conduct") }}
          <span
            v-if="evaluationsIConduct.length"
            class="ml-1 bg-blue-100 text-blue-700 text-xs rounded-full px-2"
          >{{ evaluationsIConduct.length }}</span>
        </button>
      </nav>
    </div>

    <!-- Tab: Evaluations of me -->
    <div v-if="activeTab === 'evaluatee'">
      <BaseTable
        :is-loading="loadingMe"
        :values="evaluationsOfMe"
      >
        <Column :header="t('Evaluator')">
          <template #body="{ data }">
            {{ data.evaluatorUser?.fullName }}
          </template>
        </Column>
        <Column :header="t('Template')">
          <template #body="{ data }">
            {{ data.template?.title }}
          </template>
        </Column>
        <Column :header="t('Stage')">
          <template #body="{ data }">
            {{ data.stage?.title }}
          </template>
        </Column>
        <Column :header="t('Scheduled date')">
          <template #body="{ data }">
            {{ formatDate(data.scheduledAt) }}
          </template>
        </Column>
        <Column :header="t('Status')">
          <template #body="{ data }">
            <span :class="statusBadge(data.status)">{{ t(statusLabel(data.status)) }}</span>
          </template>
        </Column>
        <Column :header="t('Score')">
          <template #body="{ data }">
            {{ data.totalScore !== null ? Number(data.totalScore).toFixed(2) : "—" }}
          </template>
        </Column>
        <Column :exportable="false">
          <template #body="{ data }">
            <BaseButton
              :label="t('Open')"
              icon="eye-on"
              only-icon
              size="small"
              type="primary-text"
              @click="go(data)"
            />
          </template>
        </Column>
      </BaseTable>
    </div>

    <!-- Tab: Evaluations I conduct -->
    <div v-if="activeTab === 'evaluator'">
      <BaseTable
        :is-loading="loadingConduct"
        :values="evaluationsIConduct"
      >
        <Column :header="t('Evaluatee')">
          <template #body="{ data }">
            {{ data.evaluatedUser?.fullName }}
          </template>
        </Column>
        <Column :header="t('Template')">
          <template #body="{ data }">
            {{ data.template?.title }}
          </template>
        </Column>
        <Column :header="t('Stage')">
          <template #body="{ data }">
            {{ data.stage?.title }}
          </template>
        </Column>
        <Column :header="t('Scheduled date')">
          <template #body="{ data }">
            {{ formatDate(data.scheduledAt) }}
          </template>
        </Column>
        <Column :header="t('Status')">
          <template #body="{ data }">
            <span :class="statusBadge(data.status)">{{ t(statusLabel(data.status)) }}</span>
          </template>
        </Column>
        <Column :exportable="false">
          <template #body="{ data }">
            <BaseButton
              :label="t('Open')"
              icon="eye-on"
              only-icon
              size="small"
              type="primary-text"
              @click="go(data)"
            />
          </template>
        </Column>
      </BaseTable>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import baseService from "../../services/baseService"

const { t } = useI18n()
const router = useRouter()
const { showErrorNotification } = useNotification()

const activeTab = ref("evaluatee")
const evaluationsOfMe = ref([])
const evaluationsIConduct = ref([])
const loadingMe = ref(false)
const loadingConduct = ref(false)

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString() : ""
}

function statusLabel(status) {
  const map = { scheduled: "Scheduled", done: "Done", feedbacked: "Feedbacked", closed: "Closed" }
  return map[status] ?? status
}

function statusBadge(status) {
  const map = {
    scheduled: "bg-blue-100 text-blue-700",
    done: "bg-yellow-100 text-yellow-700",
    feedbacked: "bg-purple-100 text-purple-700",
    closed: "bg-gray-100 text-gray-700",
  }
  return (map[status] ?? "bg-gray-100 text-gray-700") + " text-xs px-2 py-0.5 rounded-full font-medium"
}

function go(item) {
  router.push({ name: "HrEvaluationExecute", params: { id: item.id } })
}

async function switchTab(tab) {
  activeTab.value = tab
  if ("evaluatee" === tab && !evaluationsOfMe.value.length) await loadMe()
  if ("evaluator" === tab && !evaluationsIConduct.value.length) await loadConduct()
}

async function loadMe() {
  loadingMe.value = true
  try {
    const res = await baseService.get("/api/me/performance_appraisals?role=evaluatee")
    evaluationsOfMe.value = Array.isArray(res) ? res : (res["hydra:member"] ?? [])
  } catch {
    showErrorNotification(t("Could not load evaluations"))
  } finally {
    loadingMe.value = false
  }
}

async function loadConduct() {
  loadingConduct.value = true
  try {
    const res = await baseService.get("/api/me/performance_appraisals?role=evaluator")
    evaluationsIConduct.value = Array.isArray(res) ? res : (res["hydra:member"] ?? [])
  } catch {
    showErrorNotification(t("Could not load evaluations"))
  } finally {
    loadingConduct.value = false
  }
}

onMounted(loadMe)
</script>
