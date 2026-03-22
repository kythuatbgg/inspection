// Pure barrel re-export — all logic lives in inspectionDraft.js
// No imports here to avoid triggering dexie module before mock factory runs
export { db, syncService, saveDraft, getDrafts, getPendingInspections, markAsSynced } from './inspectionDraft'
