<template>
  <div class="business-unit-page">
    <SectionHeader :title="t('Business units')">
      <BaseButton
        :label="t('Add business unit')"
        icon="plus"
        type="success"
        @click="openForm()"
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
      <Column :header="t('Description')">
        <template #body="{ data }">
          {{ data.description || "—" }}
        </template>
      </Column>
      <Column :header="t('Parent unit')">
        <template #body="{ data }">
          {{ data.parentTitle || "—" }}
        </template>
      </Column>
      <Column :header="t('Primary branch')">
        <template #body="{ data }">
          {{ data.branchTitle || "—" }}
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
              @click="openForm(data)"
            />
            <BaseButton
              :label="t('Delete')"
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
      v-model:is-visible="dialog"
      :style="{ width: '480px' }"
      :title="editing ? t('Edit business unit') : t('Add business unit')"
    >
      <BaseInputText
        id="unit-title"
        v-model="form.title"
        :label="t('Title')"
        name="unit_title"
      />
      <BaseTextArea
        id="unit-description"
        v-model="form.description"
        label="Description"
        name="unit_description"
        rows="2"
      />
      <BaseSelect
        id="unit-parent"
        v-model="form.parent"
        :label="t('Parent unit')"
        :options="parentOptions"
        allow-cleared
        name="unit_parent"
      />
      <BaseSelect
        id="unit-branch"
        v-model="form.branch"
        :label="t('Primary branch')"
        :options="branchOptions"
        allow-cleared
        name="unit_branch"
      />

      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="dialog = false"
        />
        <BaseButton
          :disabled="!form.title"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="save"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import baseService from "../../services/baseService"
import * as branchService from "../../services/hr/branchService"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const items = ref([])
const branches = ref([])
const isLoading = ref(true)
const dialog = ref(false)
const editing = ref(null)
const form = ref({ title: "", description: "", parent: null, branch: null })

const parentOptions = computed(() => {
  const filtered = editing.value ? items.value.filter((u) => u["@id"] !== editing.value["@id"]) : items.value
  return filtered.map((u) => ({ label: u.title, value: u["@id"] }))
})

const branchOptions = computed(() => branches.value.map((b) => ({ label: b.title, value: b["@id"] })))

async function load() {
  isLoading.value = true
  try {
    const [unitResult, branchItems] = await Promise.all([
      baseService.getCollection("/api/business_units", { pagination: false }),
      branchService.getAll(),
    ])
    items.value = unitResult.items
    branches.value = branchItems
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

function openForm(item = null) {
  editing.value = item
  form.value = {
    title: item ? item.title : "",
    description: item ? (item.description ?? "") : "",
    parent: item ? (item.parentIri ?? null) : null,
    branch: item ? (item.branchIri ?? null) : null,
  }
  dialog.value = true
}

async function save() {
  const payload = {
    title: form.value.title,
    description: form.value.description || null,
    parent: form.value.parent,
    branch: form.value.branch,
  }
  try {
    if (editing.value) {
      await baseService.put(editing.value["@id"], payload)
    } else {
      await baseService.post("/api/business_units", payload, true)
    }
    dialog.value = false
    showSuccessNotification(t("Saved"))
    await load()
  } catch (e) {
    showErrorNotification(e)
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await baseService.delete(item["@id"])
        showSuccessNotification(t("Deleted"))
        await load()
      } catch (e) {
        showErrorNotification(e)
      }
    },
  })
}

onMounted(load)
</script>
