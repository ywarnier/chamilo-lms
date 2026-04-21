<template>
  <div class="hr-benefits space-y-8">
    <!-- Benefit Tags -->
    <section>
      <SectionHeader :title="t('Benefit tags')">
        <BaseButton
          :label="t('Add tag')"
          icon="plus"
          type="success"
          @click="openTagForm()"
        />
      </SectionHeader>

      <BaseTable
        :is-loading="tagsLoading"
        :values="tags"
      >
        <Column
          :header="t('Title')"
          field="title"
          sortable
        />
        <Column :header="t('Color')">
          <template #body="{ data }">
            <span class="inline-flex items-center gap-2">
              <span
                :style="{ backgroundColor: data.color }"
                class="inline-block w-5 h-5 rounded border border-gray-200"
              ></span>
              <span class="text-xs text-gray-500">{{ data.color }}</span>
            </span>
          </template>
        </Column>
        <Column :exportable="false">
          <template #body="{ data }">
            <div class="flex justify-end gap-2">
              <BaseButton
                :label="t('Edit')"
                icon="pencil"
                only-icon
                size="small"
                type="secondary-text"
                @click="openTagForm(data)"
              />
              <BaseButton
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeleteTag(data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </section>

    <!-- Benefits (Compensations) -->
    <section>
      <SectionHeader :title="t('Benefits')">
        <BaseButton
          :label="t('Add benefit')"
          icon="plus"
          type="success"
          @click="openCompensationForm()"
        />
      </SectionHeader>

      <BaseTable
        :is-loading="compensationsLoading"
        :values="compensations"
      >
        <Column
          :header="t('Title')"
          field="title"
          sortable
        />
        <Column :header="t('Tags')">
          <template #body="{ data }">
            <span
              v-for="tag in data.tags"
              :key="tag.id"
              :style="{ backgroundColor: tag.color }"
              class="inline-block px-2 py-0.5 rounded text-white text-xs mr-1 mb-1"
              v-text="tag.title"
            />
            <span
              v-if="!data.tags || !data.tags.length"
              class="text-gray-400 text-sm"
              v-text="'—'"
            />
          </template>
        </Column>
        <Column
          :header="t('Duration')"
          field="duration"
        >
          <template #body="{ data }">
            {{ data.duration || "—" }}
          </template>
        </Column>
        <Column
          :header="t('Score')"
          field="score"
        />
        <Column :exportable="false">
          <template #body="{ data }">
            <div class="flex justify-end gap-2">
              <BaseButton
                :label="t('Edit')"
                icon="pencil"
                only-icon
                size="small"
                type="secondary-text"
                @click="openCompensationForm(data)"
              />
              <BaseButton
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeleteCompensation(data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </section>

    <!-- Tag dialog -->
    <BaseDialog
      v-model:is-visible="tagDialog"
      :title="editingTag ? t('Edit tag') : t('Add tag')"
      :style="{ width: '400px' }"
    >
      <div class="space-y-4 pt-2">
        <BaseInputText
          id="tag-title"
          v-model="tagForm.title"
          :label="t('Title')"
          name="title"
        />
        <BaseTextArea
          id="tag-description"
          v-model="tagForm.description"
          label="Description"
          name="description"
          rows="3"
        />
        <BaseColorPicker
          v-model="tagForm.color"
          :label="t('Color')"
        />
      </div>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="tagDialog = false"
        />
        <BaseButton
          :disabled="!tagForm.title"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveTag"
        />
      </template>
    </BaseDialog>

    <!-- Compensation dialog -->
    <BaseDialog
      v-model:is-visible="compensationDialog"
      :title="editingCompensation ? t('Edit benefit') : t('Add benefit')"
      :style="{ width: '520px' }"
    >
      <BaseInputText
        id="compensation-title"
        v-model="compensationForm.title"
        :label="t('Title')"
        name="title"
      />
      <BaseTextArea
        id="compensation-description"
        v-model="compensationForm.description"
        label="Description"
        name="description"
        rows="3"
      />
      <div class="flex gap-4 mb-4">
        <div class="flex-1">
          <BaseInputText
            id="compensation-duration"
            v-model="compensationForm.duration"
            :label="t('Duration')"
            name="duration"
          />
        </div>
        <div class="w-32">
          <BaseInputNumber
            id="compensation-score"
            v-model="compensationForm.score"
            :label="t('Score')"
            :min="0"
            :step="0.1"
            name="score"
          />
        </div>
      </div>
      <Fieldset :legend="t('Tags')">
        <div class="flex flex-wrap gap-x-4">
          <BaseCheckbox
            v-for="tag in tags"
            :id="`tag-checkbox-${tag.id}`"
            :key="tag['@id']"
            v-model="compensationForm.tags"
            :label="tag.title"
            :style="{ color: tag.color }"
            :value="tag['@id']"
            name="tags"
          />
          <span
            v-if="!tags.length"
            class="text-sm text-gray-400"
          >
            {{ t("No tags available") }}
          </span>
        </div>
      </Fieldset>
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="compensationDialog = false"
        />
        <BaseButton
          :disabled="!compensationForm.title"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveCompensation"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import Color from "colorjs.io"
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import Fieldset from "primevue/fieldset"
import { useNotification } from "../../composables/notification"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseColorPicker from "../../components/basecomponents/BaseColorPicker.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import * as compensationService from "../../services/hr/compensationService"
import * as compensationTagService from "../../services/hr/compensationTagService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const tags = ref([])
const tagsLoading = ref(true)
const compensations = ref([])
const compensationsLoading = ref(true)

const tagDialog = ref(false)
const compensationDialog = ref(false)
const editingTag = ref(null)
const editingCompensation = ref(null)

const tagForm = ref({ title: "", description: "", color: new Color("#3B82F6") })
const compensationForm = ref({ title: "", description: "", duration: "", score: 0, tags: [] })

async function loadTags() {
  tagsLoading.value = true
  try {
    tags.value = await compensationTagService.getAll()
  } catch (e) {
    console.error(e)
  } finally {
    tagsLoading.value = false
  }
}

async function loadCompensations() {
  compensationsLoading.value = true
  try {
    compensations.value = await compensationService.getAll()
  } catch (e) {
    console.error(e)
  } finally {
    compensationsLoading.value = false
  }
}

function openTagForm(item = null) {
  editingTag.value = item
  tagForm.value = {
    title: item ? item.title : "",
    description: item ? (item.description ?? "") : "",
    color: item ? new Color(item.color) : new Color("#3B82F6"),
  }
  tagDialog.value = true
}

function openCompensationForm(item = null) {
  editingCompensation.value = item
  compensationForm.value = {
    title: item ? item.title : "",
    description: item ? (item.description ?? "") : "",
    duration: item ? (item.duration ?? "") : "",
    score: item ? item.score : 0,
    tags: item && item.tags ? item.tags.map((tag) => tag["@id"]) : [],
  }
  compensationDialog.value = true
}

async function saveTag() {
  const payload = {
    title: tagForm.value.title,
    description: tagForm.value.description || null,
    color: tagForm.value.color.toString({ format: "hex" }),
  }
  try {
    if (editingTag.value) {
      await compensationTagService.update(editingTag.value["@id"], payload)
    } else {
      await compensationTagService.create(payload)
    }
    tagDialog.value = false
    showSuccessNotification(t("Saved"))
    await loadTags()
    await loadCompensations()
  } catch (e) {
    showErrorNotification(e)
  }
}

async function saveCompensation() {
  const payload = {
    title: compensationForm.value.title,
    description: compensationForm.value.description || null,
    duration: compensationForm.value.duration || null,
    score: compensationForm.value.score,
    tags: compensationForm.value.tags,
  }
  try {
    if (editingCompensation.value) {
      await compensationService.update(editingCompensation.value["@id"], payload)
    } else {
      await compensationService.create(payload)
    }
    compensationDialog.value = false
    showSuccessNotification(t("Saved"))
    await loadCompensations()
  } catch (e) {
    showErrorNotification(e)
  }
}

function confirmDeleteTag(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await compensationTagService.remove(item["@id"])
        showSuccessNotification(t("Deleted"))
        await loadTags()
        await loadCompensations()
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

function confirmDeleteCompensation(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await compensationService.remove(item["@id"])
        showSuccessNotification(t("Deleted"))
        await loadCompensations()
      } catch {
        showErrorNotification(t("Cannot delete a benefit that has active assignments."))
      }
    },
  })
}

onMounted(async () => {
  await loadTags()
  await loadCompensations()
})
</script>
