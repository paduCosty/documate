"use client"

import { useState } from "react"
import { router } from "@inertiajs/react"
import {
  Plus, Trash2, Edit2, Globe, User, ChevronRight, X, Save,
} from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateButton } from "@/components/documate/documate-button"
import { cn } from "@/lib/utils"

interface Template {
  id: number
  slug: string
  name: string
  description: string | null
  is_system: boolean
  user_id: number | null
  created_at: string
}

interface Props {
  templates: Template[]
}

interface TemplateForm {
  name: string
  description: string
  prompt_template: string
  output_schema: string
}

const EMPTY_FORM: TemplateForm = {
  name: "",
  description: "",
  prompt_template:
    "You are an expert document analyst. Analyse the following text extracted from a PDF and return ONLY a valid JSON object.\n\nPDF Text:\n{pdf_text}\n\nReturn EXACTLY this JSON structure:\n{output_schema}",
  output_schema: JSON.stringify({ type: "object", properties: {} }, null, 2),
}

export default function ExtractionTemplatesPage({ templates }: Props) {
  const [showForm, setShowForm]       = useState(false)
  const [editing, setEditing]         = useState<Template | null>(null)
  const [form, setForm]               = useState<TemplateForm>(EMPTY_FORM)
  const [errors, setErrors]           = useState<Record<string, string>>({})
  const [isSaving, setIsSaving]       = useState(false)
  const [deleteConfirm, setDeleteConfirm] = useState<number | null>(null)

  const system = templates.filter((t) => t.is_system)
  const custom = templates.filter((t) => !t.is_system)

  const openCreate = () => {
    setEditing(null)
    setForm(EMPTY_FORM)
    setErrors({})
    setShowForm(true)
  }

  const openEdit = (t: Template) => {
    setEditing(t)
    setForm({
      name: t.name,
      description: t.description ?? "",
      prompt_template: "",
      output_schema: "{}",
    })
    setErrors({})
    setShowForm(true)
  }

  const handleSave = () => {
    setErrors({})
    setIsSaving(true)

    let parsedSchema: unknown
    try {
      parsedSchema = JSON.parse(form.output_schema)
    } catch {
      setErrors({ output_schema: "Invalid JSON schema." })
      setIsSaving(false)
      return
    }

    const payload = {
      name: form.name,
      description: form.description || undefined,
      prompt_template: form.prompt_template,
      output_schema: parsedSchema,
    }

    if (editing) {
      router.put(route("extraction.templates.update", editing.id), payload, {
        onError: (e) => { setErrors(e); setIsSaving(false) },
        onSuccess: () => { setShowForm(false); setIsSaving(false) },
      })
    } else {
      router.post(route("extraction.templates.store"), payload, {
        onError: (e) => { setErrors(e); setIsSaving(false) },
        onSuccess: () => { setShowForm(false); setIsSaving(false) },
      })
    }
  }

  const handleDelete = (id: number) => {
    router.delete(route("extraction.templates.destroy", id), {
      onSuccess: () => setDeleteConfirm(null),
    })
  }

  return (
    <div className="min-h-screen bg-zinc-950">
      <Navbar />

      <main className="mx-auto max-w-3xl px-6 py-16">
        <div className="mb-8 flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-white">Extraction Templates</h1>
            <p className="mt-1 text-sm text-zinc-400">
              Templates define how AI extracts data from your PDFs.
            </p>
          </div>
          <DocumateButton onClick={openCreate} size="sm">
            <Plus className="h-4 w-4" />
            New Template
          </DocumateButton>
        </div>

        {/* System templates */}
        {system.length > 0 && (
          <section className="mb-8">
            <p className="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-500">
              System Templates
            </p>
            <div className="space-y-2">
              {system.map((t) => (
                <TemplateRow key={t.id} template={t} isSystem />
              ))}
            </div>
          </section>
        )}

        {/* Custom templates */}
        <section>
          <p className="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-500">
            My Templates
          </p>
          {custom.length === 0 ? (
            <DocumateCard className="py-12 text-center">
              <p className="text-sm text-zinc-500">You haven't created any custom templates yet.</p>
              <DocumateButton variant="outline" size="sm" className="mt-4" onClick={openCreate}>
                <Plus className="h-4 w-4" />
                Create your first template
              </DocumateButton>
            </DocumateCard>
          ) : (
            <div className="space-y-2">
              {custom.map((t) => (
                <TemplateRow
                  key={t.id}
                  template={t}
                  onEdit={() => openEdit(t)}
                  onDelete={() => setDeleteConfirm(t.id)}
                  confirmDelete={deleteConfirm === t.id}
                  onConfirmDelete={() => handleDelete(t.id)}
                  onCancelDelete={() => setDeleteConfirm(null)}
                />
              ))}
            </div>
          )}
        </section>
      </main>

      {/* Slide-over form */}
      {showForm && (
        <div className="fixed inset-0 z-50 flex">
          <div
            className="flex-1 bg-black/60 backdrop-blur-sm"
            onClick={() => setShowForm(false)}
          />
          <div className="flex w-full max-w-lg flex-col bg-zinc-900 shadow-2xl">
            {/* Header */}
            <div className="flex items-center justify-between border-b border-zinc-800 px-6 py-4">
              <h2 className="text-lg font-semibold text-white">
                {editing ? "Edit Template" : "New Template"}
              </h2>
              <button
                type="button"
                onClick={() => setShowForm(false)}
                className="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-800 hover:text-white"
              >
                <X className="h-4 w-4" />
              </button>
            </div>

            {/* Body */}
            <div className="flex-1 overflow-y-auto px-6 py-6 space-y-5">
              <Field label="Template Name" error={errors.name}>
                <input
                  type="text"
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  placeholder="e.g. Romanian VAT Invoice"
                  className="input-base"
                />
              </Field>

              <Field label="Description (optional)" error={errors.description}>
                <input
                  type="text"
                  value={form.description}
                  onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                  placeholder="What kind of documents does this template handle?"
                  className="input-base"
                />
              </Field>

              <Field
                label="Prompt Template"
                hint="Must contain {pdf_text} and {output_schema}"
                error={errors.prompt_template}
              >
                <textarea
                  rows={8}
                  value={form.prompt_template}
                  onChange={(e) => setForm((f) => ({ ...f, prompt_template: e.target.value }))}
                  className="input-base font-mono text-xs"
                />
              </Field>

              <Field
                label="Output Schema (JSON)"
                hint="JSON Schema object describing the expected output fields"
                error={errors.output_schema}
              >
                <textarea
                  rows={10}
                  value={form.output_schema}
                  onChange={(e) => setForm((f) => ({ ...f, output_schema: e.target.value }))}
                  className="input-base font-mono text-xs"
                />
              </Field>
            </div>

            {/* Footer */}
            <div className="flex justify-end gap-3 border-t border-zinc-800 px-6 py-4">
              <DocumateButton variant="ghost" onClick={() => setShowForm(false)}>
                Cancel
              </DocumateButton>
              <DocumateButton onClick={handleSave} loading={isSaving}>
                <Save className="h-4 w-4" />
                {editing ? "Save Changes" : "Create Template"}
              </DocumateButton>
            </div>
          </div>
        </div>
      )}

      <Footer />
    </div>
  )
}

// ─── Sub-components ──────────────────────────────────────────────────────────

function TemplateRow({
  template,
  isSystem = false,
  onEdit,
  onDelete,
  confirmDelete = false,
  onConfirmDelete,
  onCancelDelete,
}: {
  template: Template
  isSystem?: boolean
  onEdit?: () => void
  onDelete?: () => void
  confirmDelete?: boolean
  onConfirmDelete?: () => void
  onCancelDelete?: () => void
}) {
  return (
    <DocumateCard padding="none">
      <div className="flex items-center gap-3 px-4 py-3.5">
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-800">
          {isSystem ? (
            <Globe className="h-4 w-4 text-zinc-400" />
          ) : (
            <User className="h-4 w-4 text-zinc-400" />
          )}
        </div>

        <div className="min-w-0 flex-1">
          <p className="truncate text-sm font-medium text-white">{template.name}</p>
          {template.description && (
            <p className="truncate text-xs text-zinc-500">{template.description}</p>
          )}
        </div>

        {!isSystem && !confirmDelete && (
          <div className="flex shrink-0 items-center gap-1">
            <button
              type="button"
              onClick={onEdit}
              className="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-800 hover:text-zinc-200"
            >
              <Edit2 className="h-3.5 w-3.5" />
            </button>
            <button
              type="button"
              onClick={onDelete}
              className="rounded-lg p-1.5 text-zinc-500 hover:bg-red-950 hover:text-red-400"
            >
              <Trash2 className="h-3.5 w-3.5" />
            </button>
          </div>
        )}

        {confirmDelete && (
          <div className="flex shrink-0 items-center gap-2">
            <span className="text-xs text-red-400">Delete?</span>
            <button
              type="button"
              onClick={onConfirmDelete}
              className="rounded-md bg-red-950 px-2.5 py-1 text-xs font-medium text-red-300 hover:bg-red-900"
            >
              Yes
            </button>
            <button
              type="button"
              onClick={onCancelDelete}
              className="rounded-md bg-zinc-800 px-2.5 py-1 text-xs font-medium text-zinc-400 hover:bg-zinc-700"
            >
              No
            </button>
          </div>
        )}
      </div>
    </DocumateCard>
  )
}

function Field({
  label,
  hint,
  error,
  children,
}: {
  label: string
  hint?: string
  error?: string
  children: React.ReactNode
}) {
  return (
    <div className="space-y-1.5">
      <div className="flex items-baseline justify-between">
        <label className="text-xs font-medium text-zinc-300">{label}</label>
        {hint && <span className="text-[10px] text-zinc-600">{hint}</span>}
      </div>
      {children}
      {error && <p className="text-xs text-red-400">{error}</p>}
    </div>
  )
}
