import baseService from "../baseService"

export async function getAll() {
  const result = await baseService.getCollection("/api/compensations", { pagination: false })
  return result.items
}

export async function create(payload) {
  await baseService.post("/api/compensations", payload)
}

export async function update(iri, payload) {
  await baseService.put(iri, payload)
}

export async function remove(iri) {
  await baseService.delete(iri)
}
