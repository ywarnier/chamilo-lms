<template>
  <div>
    <Toolbar
      :handle-delete="del"
      :handle-reset="resetForm"
      :handle-submit="onSendForm"
    />
    <CourseCategoryForm
      v-if="item"
      ref="updateForm"
      :errors="violations"
      :values="item"
    />
    <Loading :visible="isLoading || deleteLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import CourseCategoryForm from "../../components/coursecategory/Form.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import UpdateMixin from "../../mixins/UpdateMixin"

const servicePrefix = "CourseCategory"

export default {
  name: "CourseCategoryUpdate",
  servicePrefix,
  mixins: [UpdateMixin],
  components: {
    Loading,
    Toolbar,
    CourseCategoryForm,
  },

  computed: {
    ...mapFields("coursecategory", {
      deleteLoading: "isLoading",
      isLoading: "isLoading",
      error: "error",
      updated: "updated",
      violations: "violations",
    }),
    ...mapGetters("coursecategory", ["find"]),
  },

  methods: {
    ...mapActions("coursecategory", {
      createReset: "resetCreate",
      deleteItem: "del",
      delReset: "resetDelete",
      retrieve: "load",
      update: "update",
      updateReset: "resetUpdate",
    }),
  },
}
</script>
