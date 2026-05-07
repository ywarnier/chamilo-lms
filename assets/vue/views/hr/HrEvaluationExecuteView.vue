<template>
  <div class="space-y-6 max-w-5xl">
    <SectionHeader :title="t('Evaluation form')">
      <BaseButton
        :label="t('Back')"
        icon="arrow-left"
        type="plain"
        @click="router.back()"
      />
    </SectionHeader>

    <div
      v-if="loading"
      class="text-center py-12 text-gray-400"
    >
      {{ t("Loading…") }}
    </div>

    <template v-else-if="appraisal">
      <!-- Header info -->
      <div class="bg-white rounded border border-gray-200 p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div>
          <p class="text-gray-400">
            {{ t("Evaluatee") }}
          </p>
          <p class="font-semibold">
            {{ appraisal.evaluatedUser?.fullName }}
          </p>
        </div>
        <div>
          <p class="text-gray-400">
            {{ t("Evaluator") }}
          </p>
          <p class="font-semibold">
            {{ appraisal.evaluatorUser?.fullName }}
          </p>
        </div>
        <div>
          <p class="text-gray-400">
            {{ t("Scheduled date") }}
          </p>
          <p class="font-semibold">
            {{ formatDate(appraisal.scheduledAt) }}
          </p>
        </div>
        <div>
          <p class="text-gray-400">
            {{ t("Status") }}
          </p>
          <span :class="statusBadge(appraisal.status)">{{ t(statusLabel(appraisal.status)) }}</span>
        </div>
        <div>
          <p class="text-gray-400">
            {{ t("Template") }}
          </p>
          <p class="font-semibold">
            {{ appraisal.template?.title }}
          </p>
        </div>
        <div>
          <p class="text-gray-400">
            {{ t("Periodicity") }}
          </p>
          <p class="font-semibold">
            {{ appraisal.template?.periodicity?.title ?? "—" }}
          </p>
        </div>
        <div>
          <p class="text-gray-400">
            {{ t("Stage") }}
          </p>
          <p class="font-semibold">
            {{ appraisal.stage?.title }}
          </p>
        </div>
        <div>
          <p class="text-gray-400">
            {{ t("Total score") }}
          </p>
          <p class="font-semibold text-blue-700">
            {{ appraisal.totalScore !== null ? Number(appraisal.totalScore).toFixed(2) : "—" }}
          </p>
        </div>
      </div>

      <!-- History chart (evaluator only) -->
      <div
        v-if="isEvaluator && historyData.labels.length"
        class="bg-white rounded border border-gray-200 p-6"
      >
        <h3 class="font-semibold text-gray-700 mb-4">
          {{ t("History of evaluations") }}
        </h3>
        <Chart
          :data="chartData"
          :options="chartOptions"
          class="h-48"
          type="line"
        />
      </div>

      <!-- Items table -->
      <div class="bg-white rounded border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 font-semibold text-gray-600">
                {{ t("Item") }}
              </th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600 w-24">
                {{ t("Type") }}
              </th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600 w-20">
                {{ t("Weight") }}
              </th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600 w-32">
                {{ t("Score") }}
              </th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600">
                {{ t("Evaluator comment") }}
              </th>
              <th class="text-left px-4 py-3 font-semibold text-gray-600">
                {{ t("Evaluatee comment") }}
              </th>
              <th class="px-4 py-3 w-10" />
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in appraisal.items"
              :key="item.id"
              class="border-b last:border-0 hover:bg-gray-50"
            >
              <td class="px-4 py-3 font-medium">
                {{ refLabel(item) }}
              </td>
              <td class="px-4 py-3">
                <span class="text-xs uppercase text-gray-400">{{ item.type }}</span>
              </td>
              <td class="px-4 py-3 text-gray-500">
                {{ item.percentage }}%
              </td>
              <td class="px-4 py-3">
                <select
                  v-if="canEditScore"
                  v-model="itemEdits[item.id].score"
                  class="border border-gray-300 rounded px-2 py-1 text-sm w-full"
                  :name="'score_' + item.id"
                >
                  <option value="">
                    —
                  </option>
                  <option
                    v-for="n in 5"
                    :key="n"
                    :value="n"
                  >
                    {{ n }}
                  </option>
                </select>
                <span v-else>{{ item.score ?? "—" }}</span>
              </td>
              <td class="px-4 py-3">
                <textarea
                  v-if="canEditResponsibleComment"
                  v-model="itemEdits[item.id].responsibleComment"
                  class="border border-gray-300 rounded px-2 py-1 text-sm w-full"
                  :name="'resp_comment_' + item.id"
                  rows="2"
                />
                <span
                  v-else
                  class="text-gray-600"
                >{{ item.responsibleComment || "—" }}</span>
              </td>
              <td class="px-4 py-3">
                <textarea
                  v-if="canEditCollaboratorComment"
                  v-model="itemEdits[item.id].collaboratorComment"
                  class="border border-gray-300 rounded px-2 py-1 text-sm w-full"
                  :name="'collab_comment_' + item.id"
                  rows="2"
                />
                <span
                  v-else
                  class="text-gray-600"
                >{{ item.collaboratorComment || "—" }}</span>
              </td>
              <td class="px-4 py-3">
                <BaseButton
                  :label="t('Comments') + (item.comments?.length ? ' (' + item.comments.length + ')' : '')"
                  icon="comment"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="openComments(item)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Action buttons -->
      <div class="flex flex-wrap gap-3 justify-end">
        <BaseButton
          v-if="canSaveDraft"
          :label="t('Save draft')"
          :loading="savingDraft"
          icon="content-save-outline"
          type="secondary"
          @click="saveDraft"
        />
        <BaseButton
          v-if="canSendToEvaluatee"
          :label="t('Send to evaluatee')"
          :loading="sendingToEvaluatee"
          icon="send"
          type="success"
          @click="sendToEvaluatee"
        />
        <BaseButton
          v-if="canSubmitFeedback"
          :label="t('Submit feedback')"
          :loading="submittingFeedback"
          icon="send"
          type="success"
          @click="submitFeedback"
        />
        <BaseButton
          v-if="canClose"
          :label="t('Close evaluation')"
          :loading="closing"
          icon="check-circle"
          type="primary"
          @click="closeEvaluation"
        />
      </div>

      <!-- Action plan (evaluator only, after sending) -->
      <div
        v-if="isEvaluator && ['done', 'feedbacked', 'closed'].includes(appraisal.status)"
        class="bg-white rounded border border-gray-200 p-6 space-y-4"
      >
        <h3 class="font-semibold text-gray-700">
          {{ t("Action plan for next period") }}
        </h3>
        <p
          v-if="appraisal.status === 'closed' && !actionPlan.items.length"
          class="text-sm text-gray-400"
        >
          {{ t("No action plan defined.") }}
        </p>
        <template v-if="appraisal.status !== 'closed'">
          <div class="flex flex-col gap-2 w-fit">
            <BaseInputText
              id="action-plan-date"
              v-model="actionPlan.scheduledAt"
              :label="t('Next evaluation date')"
              name="actionPlanDate"
              type="datetime-local"
            />
            <BaseButton
              v-if="hasPeriodicity"
              :label="t('Suggest from periodicity')"
              icon="calendar"
              type="secondary"
              @click="suggestFromPeriodicity"
            />
          </div>
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-600">{{ t("Items") }}</span>
              <div class="flex gap-2">
                <BaseButton
                  :label="t('+ Skill')"
                  size="small"
                  type="secondary"
                  @click="addActionPlanItem('skill')"
                />
                <BaseButton
                  :label="t('+ Activity')"
                  size="small"
                  type="secondary"
                  @click="addActionPlanItem('activity')"
                />
                <BaseButton
                  :label="t('+ Objective')"
                  size="small"
                  type="secondary"
                  @click="addActionPlanItem('objective')"
                />
              </div>
            </div>
            <div
              v-for="(item, idx) in actionPlan.items"
              :key="idx"
              class="flex gap-3 items-end"
            >
              <div class="flex-1">
                <select
                  v-model="item.ref"
                  class="border border-gray-300 rounded px-2 py-1 text-sm w-full"
                  :name="'ap_ref_' + idx"
                >
                  <option
                    v-for="opt in getRefOptions(item.type)"
                    :key="opt.id"
                    :value="opt.id"
                  >
                    {{ opt.title }}
                  </option>
                </select>
              </div>
              <div class="w-24">
                <input
                  v-model.number="item.percentage"
                  class="border border-gray-300 rounded px-2 py-1 text-sm w-full"
                  :name="'ap_pct_' + idx"
                  placeholder="%"
                  type="number"
                />
              </div>
              <BaseButton
                :label="t('Remove')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="actionPlan.items.splice(idx, 1)"
              />
            </div>
          </div>
          <BaseButton
            :label="t('Save action plan')"
            :loading="savingActionPlan"
            icon="content-save"
            type="success"
            @click="saveActionPlan"
          />
        </template>
        <template v-else>
          <div
            v-for="item in actionPlan.items"
            :key="item.ref + item.type"
            class="flex gap-4 text-sm"
          >
            <span class="text-gray-400 uppercase text-xs w-20">{{ item.type }}</span>
            <span>{{ refLabel(item) }}</span>
            <span class="text-gray-400">{{ item.percentage }}%</span>
          </div>
        </template>
      </div>
    </template>

    <!-- Comments dialog -->
    <BaseDialog
      v-if="commentsDialog.visible"
      :title="t('Comments')"
      :visible="commentsDialog.visible"
      @close="commentsDialog.visible = false"
    >
      <div class="space-y-3 max-h-64 overflow-y-auto">
        <div
          v-for="c in commentsDialog.comments"
          :key="c.id"
          class="bg-gray-50 rounded p-3 text-sm"
        >
          <div class="flex justify-between text-xs text-gray-400 mb-1">
            <span class="font-medium text-gray-600">{{ c.sender?.fullName ?? c.sender }}</span>
            <span>{{ c.createdOn }}</span>
          </div>
          <p class="text-gray-700">
            {{ c.comment }}
          </p>
        </div>
        <p
          v-if="!commentsDialog.comments.length"
          class="text-gray-400 text-sm"
        >
          {{ t("No comments yet.") }}
        </p>
      </div>
      <div class="mt-4 space-y-2">
        <BaseTextArea
          id="new-comment"
          v-model="commentsDialog.newComment"
          :label="t('New comment')"
          name="newComment"
          rows="3"
        />
        <BaseButton
          :label="t('Send')"
          :loading="commentsDialog.sending"
          icon="send"
          type="success"
          @click="postComment"
        />
      </div>
      <template #footer>
        <BaseButton
          :label="t('Close')"
          type="plain"
          @click="commentsDialog.visible = false"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter, useRoute } from "vue-router"
import Chart from "primevue/chart"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import { useSecurityStore } from "../../store/securityStore"
import baseService from "../../services/baseService"
import axios from "axios"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { showSuccessNotification, showErrorNotification } = useNotification()
const securityStore = useSecurityStore()

const appraisal = ref(null)
const loading = ref(false)
const savingDraft = ref(false)
const sendingToEvaluatee = ref(false)
const submittingFeedback = ref(false)
const closing = ref(false)
const savingActionPlan = ref(false)
const historyData = ref({ labels: [], scores: [] })

const skills = ref([])
const activities = ref([])
const objectives = ref([])

const itemEdits = ref({})

const actionPlan = reactive({ scheduledAt: "", items: [] })

const commentsDialog = reactive({
  visible: false,
  item: null,
  comments: [],
  newComment: "",
  sending: false,
})

const currentUserId = computed(() => securityStore.user?.id)

const isEvaluator = computed(() => {
  if (!appraisal.value || !currentUserId.value) return false
  return (
    appraisal.value.evaluatorUser?.id === currentUserId.value ||
    securityStore.isAdmin ||
    securityStore.isHRM
  )
})

const isEvaluated = computed(() => {
  if (!appraisal.value || !currentUserId.value) return false
  return appraisal.value.evaluatedUser?.id === currentUserId.value
})

const canEditScore = computed(
  () => isEvaluator.value && ["scheduled", "feedbacked"].includes(appraisal.value?.status),
)
const canEditResponsibleComment = computed(
  () => isEvaluator.value && ["scheduled", "feedbacked"].includes(appraisal.value?.status),
)
const canEditCollaboratorComment = computed(
  () => isEvaluated.value && appraisal.value?.status === "done",
)
const canSaveDraft = computed(
  () =>
    (isEvaluator.value && ["scheduled", "feedbacked"].includes(appraisal.value?.status)) ||
    (isEvaluated.value && appraisal.value?.status === "done"),
)
const canSendToEvaluatee = computed(
  () => isEvaluator.value && appraisal.value?.status === "scheduled",
)
const canSubmitFeedback = computed(
  () => isEvaluated.value && appraisal.value?.status === "done",
)
const canClose = computed(
  () => isEvaluator.value && appraisal.value?.status === "feedbacked",
)

const hasPeriodicity = computed(() => !!appraisal.value?.template?.periodicity)

function suggestFromPeriodicity() {
  const periodicity = appraisal.value?.template?.periodicity
  const days = typeof periodicity === "object" ? periodicity?.days : null
  if (!days) return
  const date = new Date(appraisal.value.scheduledAt)
  date.setDate(date.getDate() + days)
  while (date.getDay() === 0 || date.getDay() === 6) {
    date.setDate(date.getDate() + 1)
  }
  const pad = (n) => String(n).padStart(2, "0")
  actionPlan.scheduledAt = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const chartData = computed(() => ({
  labels: historyData.value.labels,
  datasets: [
    {
      label: t("Score"),
      data: historyData.value.scores,
      borderColor: "#3b82f6",
      backgroundColor: "rgba(59,130,246,0.1)",
      tension: 0.3,
      fill: true,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true, max: 5 } },
}

function refLabel(item) {
  const type = item.type
  const id = item.ref
  if (type === "skill") return skills.value.find((s) => s.id === id)?.title ?? id
  if (type === "activity") return activities.value.find((a) => a.id === id)?.title ?? id
  if (type === "objective") return objectives.value.find((o) => o.id === id)?.title ?? id
  return id
}

function getRefOptions(type) {
  if (type === "skill") return skills.value
  if (type === "activity") return activities.value
  return objectives.value
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

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString() : ""
}

function buildItemEdits() {
  const edits = {}
  for (const item of appraisal.value?.items ?? []) {
    edits[item.id] = {
      score: item.score ?? "",
      responsibleComment: item.responsibleComment ?? "",
      collaboratorComment: item.collaboratorComment ?? "",
    }
  }
  itemEdits.value = edits
}

function buildItemPayload() {
  const items = {}
  for (const [id, edit] of Object.entries(itemEdits.value)) {
    items[Number(id)] = {
      score: edit.score !== "" ? Number(edit.score) : null,
      responsibleComment: edit.responsibleComment || null,
      collaboratorComment: edit.collaboratorComment || null,
    }
  }
  return items
}

async function loadAppraisal() {
  const id = route.params.id
  const [aRes, hRes, skillRes, actRes, objRes] = await Promise.all([
    baseService.get(`/api/performance_appraisals/${id}`),
    axios.get(`/hr/evaluations/${id}/history-data`).catch(() => ({ data: { labels: [], scores: [] } })),
    baseService.get("/api/skills", { pagination: false }),
    baseService.get("/api/activities"),
    baseService.get("/api/performance_objectives"),
  ])
  appraisal.value = aRes
  historyData.value = hRes.data
  skills.value = skillRes["hydra:member"] ?? skillRes
  activities.value = actRes["hydra:member"] ?? actRes
  objectives.value = objRes["hydra:member"] ?? objRes
  buildItemEdits()

  if (aRes.actionPlan) {
    actionPlan.scheduledAt = aRes.actionPlan.scheduledAt?.slice(0, 16) ?? ""
    actionPlan.items = (aRes.actionPlan.items ?? []).map((i) => ({
      type: i.type,
      ref: i.ref,
      percentage: i.percentage,
    }))
  }
}

async function saveDraft() {
  savingDraft.value = true
  try {
    await axios.post(`/hr/evaluations/${route.params.id}/save-draft`, { items: buildItemPayload() })
    showSuccessNotification(t("Draft saved"))
    await loadAppraisal()
  } catch {
    showErrorNotification(t("Could not save draft"))
  } finally {
    savingDraft.value = false
  }
}

async function sendToEvaluatee() {
  sendingToEvaluatee.value = true
  try {
    await axios.post(`/hr/evaluations/${route.params.id}/send-to-evaluatee`, { items: buildItemPayload() })
    showSuccessNotification(t("Sent to evaluatee"))
    await loadAppraisal()
  } catch {
    showErrorNotification(t("Could not send"))
  } finally {
    sendingToEvaluatee.value = false
  }
}

async function submitFeedback() {
  submittingFeedback.value = true
  try {
    await axios.post(`/hr/evaluations/${route.params.id}/submit-feedback`, { items: buildItemPayload() })
    showSuccessNotification(t("Feedback submitted"))
    await loadAppraisal()
  } catch {
    showErrorNotification(t("Could not submit feedback"))
  } finally {
    submittingFeedback.value = false
  }
}

async function closeEvaluation() {
  closing.value = true
  try {
    await axios.post(`/hr/evaluations/${route.params.id}/close`)
    showSuccessNotification(t("Evaluation closed"))
    await loadAppraisal()
  } catch {
    showErrorNotification(t("Could not close evaluation"))
  } finally {
    closing.value = false
  }
}

function addActionPlanItem(type) {
  actionPlan.items.push({ type, ref: "", percentage: 0 })
}

async function saveActionPlan() {
  savingActionPlan.value = true
  try {
    await axios.post(`/hr/evaluations/${route.params.id}/action-plan`, {
      scheduledAt: actionPlan.scheduledAt ? new Date(actionPlan.scheduledAt).toISOString() : null,
      items: actionPlan.items.filter((i) => i.ref).map((i) => ({
        type: i.type,
        ref: Number(i.ref),
        percentage: Number(i.percentage),
      })),
    })
    showSuccessNotification(t("Action plan saved"))
    await loadAppraisal()
  } catch {
    showErrorNotification(t("Could not save action plan"))
  } finally {
    savingActionPlan.value = false
  }
}

function openComments(item) {
  commentsDialog.item = item
  commentsDialog.comments = item.comments ?? []
  commentsDialog.newComment = ""
  commentsDialog.visible = true
}

async function postComment() {
  if (!commentsDialog.newComment.trim()) return
  commentsDialog.sending = true
  try {
    const res = await axios.post(`/hr/evaluations/items/${commentsDialog.item.id}/comments`, {
      comment: commentsDialog.newComment,
    })
    commentsDialog.comments.push(res.data)
    commentsDialog.newComment = ""
    showSuccessNotification(t("Comment sent"))
  } catch {
    showErrorNotification(t("Could not send comment"))
  } finally {
    commentsDialog.sending = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await loadAppraisal()
  } catch {
    showErrorNotification(t("Could not load evaluation"))
  } finally {
    loading.value = false
  }
})
</script>
