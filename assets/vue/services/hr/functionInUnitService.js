import baseService from "../baseService"

export async function getAll(params = {}) {
  const result = await baseService.getCollection("/api/function_in_units", { pagination: false, ...params })

  return result.items
}

export async function create(payload) {
  return baseService.post("/api/function_in_units", payload)
}

export async function update(iri, payload) {
  return baseService.put(iri, payload)
}

export async function remove(iri) {
  return baseService.delete(iri)
}