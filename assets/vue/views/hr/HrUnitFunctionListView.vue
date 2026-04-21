<template>
  <div class="p-4">
    <h1 class="text-xl font-semibold mb-4">{{ t("Unit function list") }}</h1>
    <div v-if="isLoading" class="text-gray-500">{{ t("Loading…") }}</div>
    <div v-else class="flex gap-6">
      <!-- Left: tree -->
      <div class="w-1/2 overflow-auto">
        <ul class="space-y-1">
          <UnitTreeNode
            v-for="node in tree"
            :key="node.id"
            :node="node"
            :selected-id="selectedUnitId"
            @select="selectUnit"
          />
        </ul>
      </div>

      <!-- Right: doughnut chart -->
      <div class="w-1/2">
        <div v-if="selectedUnit" class="mb-2 font-medium">
          {{ selectedUnit.title }} — {{ t("Headcount") }}: {{ selectedHeadcount }}
        </div>
        <div v-else class="mb-2 text-gray-400 text-sm">{{ t("Select a unit to see headcount distribution") }}</div>
        <canvas ref="chartCanvas" style="max-height: 320px" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onActivated, defineComponent, h } from "vue"
import { useI18n } from "vue-i18n"
import { Chart, ArcElement, DoughnutController, Tooltip, Legend } from "chart.js"
import axios from "axios"

Chart.register(ArcElement, DoughnutController, Tooltip, Legend)

const { t } = useI18n()

// Recursive tree node component
const UnitTreeNode = defineComponent({
  name: "UnitTreeNode",
  props: { node: Object, selectedId: Number },
  emits: ["select"],
  setup(props, { emit }) {
    const expanded = ref(true)
    return () => {
      const n = props.node
      return h("li", { class: "pl-2" }, [
        h("div", { class: "flex items-center gap-1 cursor-pointer py-0.5" }, [
          n.children?.length
            ? h(
                "button",
                {
                  type: "button",
                  class: "text-gray-400 hover:text-gray-700 text-xs w-4",
                  onClick: () => { expanded.value = !expanded.value },
                },
                expanded.value ? "▾" : "▸",
              )
            : h("span", { class: "w-4" }),
          h(
            "button",
            {
              type: "button",
              class: [
                "text-sm px-1 rounded hover:bg-blue-50",
                props.selectedId === n.id ? "bg-blue-100 font-semibold text-blue-700" : "text-gray-700",
              ].join(" "),
              onClick: () => emit("select", n),
            },
            n.title,
          ),
          h("span", { class: "text-xs text-gray-400 ml-1" }, `(${n.headcount ?? 0})`),
        ]),
        expanded.value && n.children?.length
          ? h(
              "ul",
              { class: "pl-4 space-y-0.5" },
              n.children.map((child) =>
                h(UnitTreeNode, { node: child, selectedId: props.selectedId, onSelect: (v) => emit("select", v) }),
              ),
            )
          : null,
      ])
    }
  },
})

const tree = ref([])
const isLoading = ref(false)
const selectedUnitId = ref(null)
const chartCanvas = ref(null)
let chartInstance = null

const selectedUnit = computed(() => selectedUnitId.value ? findNode(tree.value, selectedUnitId.value) : null)

const selectedHeadcount = computed(() => {
  if (!selectedUnit.value) return 0
  return countHeadcount(selectedUnit.value)
})

function findNode(nodes, id) {
  for (const n of nodes) {
    if (n.id === id) return n
    if (n.children?.length) {
      const found = findNode(n.children, id)
      if (found) return found
    }
  }
  return null
}

function countHeadcount(node) {
  let count = node.headcount ?? 0
  for (const child of (node.children ?? [])) {
    count += countHeadcount(child)
  }
  return count
}

function gatherFunctionHeadcounts(node) {
  const result = {}
  for (const fn of (node.functions ?? [])) {
    const key = fn.professionalFunctionTitle + ' / ' + fn.title
    result[key] = (result[key] ?? 0) + fn.headcount
  }
  for (const child of (node.children ?? [])) {
    const childData = gatherFunctionHeadcounts(child)
    for (const [k, v] of Object.entries(childData)) {
      result[k] = (result[k] ?? 0) + v
    }
  }
  return result
}

function updateChart(node) {
  if (!chartCanvas.value) return
  const data = gatherFunctionHeadcounts(node)
  const labels = Object.keys(data)
  const values = Object.values(data)

  const colors = labels.map((_, i) => `hsl(${(i * 47) % 360},65%,55%)`)

  if (chartInstance) {
    chartInstance.data.labels = labels
    chartInstance.data.datasets[0].data = values
    chartInstance.data.datasets[0].backgroundColor = colors
    chartInstance.update()
    return
  }

  chartInstance = new Chart(chartCanvas.value, {
    type: "doughnut",
    data: {
      labels,
      datasets: [{ data: values, backgroundColor: colors, borderWidth: 1 }],
    },
    options: {
      responsive: true,
      plugins: { legend: { position: "bottom" } },
    },
  })
}

function selectUnit(node) {
  selectedUnitId.value = node.id
  updateChart(node)
}

watch(selectedUnit, (node) => {
  if (node) updateChart(node)
})

async function load() {
  isLoading.value = true
  try {
    const res = await axios.get("/hr/unit-function-list-data")
    tree.value = res.data.tree ?? []
  } finally {
    isLoading.value = false
  }
}

onMounted(load)
onActivated(load)
</script>
