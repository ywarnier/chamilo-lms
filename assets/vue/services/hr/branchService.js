import baseService from "../baseService"

export async function getAll(params = {}) {
  const { items } = await baseService.getCollection("/api/hr_branches", { pagination: false, ...params })

  return items
}

export async function create(payload) {
  return baseService.post("/api/hr_branches", payload, true)
}

export async function update(iri, payload) {
  return baseService.put(iri, payload)
}

export async function remove(iri) {
  return baseService.delete(iri)
}
