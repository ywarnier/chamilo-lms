import baseService from "../baseService"

export async function getAll() {
  const result = await baseService.getCollection("/api/recruitment_stages", { pagination: false })

  return result.items
}

export async function create(payload) {
  return baseService.post("/api/recruitment_stages", payload)
}

export async function update(iri, payload) {
  return baseService.put(iri, payload)
}

export async function remove(iri) {
  return baseService.delete(iri)
}
