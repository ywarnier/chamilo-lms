import makeService from "./api"
import baseService from "./baseService"

const branchService = makeService("branches")

branchService.fetchWithCounts = async () => {
  return baseService.get("/admin/branches/with-counts")
}

export async function getAll(params = {}) {
  const { items } = await baseService.getCollection("/api/branches", { pagination: false, ...params })

  return items
}

export async function create(payload) {
  return baseService.post("/api/branches", payload, true)
}

export async function update(iri, payload) {
  return baseService.put(iri, payload)
}

export async function remove(iri) {
  return baseService.delete(iri)
}

export default branchService
