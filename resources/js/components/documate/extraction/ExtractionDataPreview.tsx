import { useState } from "react"
import { cn } from "@/lib/utils"
import { ChevronDown, ChevronRight, Table2, List } from "lucide-react"

interface Props {
  data: Record<string, unknown>
}

export function ExtractionDataPreview({ data }: Props) {
  const { scalars, collections } = flattenForDisplay(data)
  const collectionKeys = Object.keys(collections)
  const [activeTab, setActiveTab] = useState<string>("__info__")

  const tabs = [
    { key: "__info__", label: "Info" },
    ...collectionKeys.map((k) => ({ key: k, label: prettifyKey(k) })),
  ]

  return (
    <div className="overflow-hidden rounded-xl border border-zinc-800">
      {/* Tab bar */}
      {tabs.length > 1 && (
        <div className="flex overflow-x-auto border-b border-zinc-800 bg-zinc-900/60">
          {tabs.map((tab) => (
            <button
              key={tab.key}
              type="button"
              onClick={() => setActiveTab(tab.key)}
              className={cn(
                "flex shrink-0 items-center gap-1.5 border-b-2 px-4 py-2.5 text-xs font-medium transition-colors",
                activeTab === tab.key
                  ? "border-white text-white"
                  : "border-transparent text-zinc-500 hover:text-zinc-300",
              )}
            >
              {tab.key === "__info__" ? (
                <List className="h-3 w-3" />
              ) : (
                <Table2 className="h-3 w-3" />
              )}
              {tab.label}
            </button>
          ))}
        </div>
      )}

      {/* Info tab: key/value table */}
      {activeTab === "__info__" && (
        <div className="overflow-x-auto">
          <table className="w-full text-xs">
            <tbody>
              {Object.entries(scalars).map(([k, v], i) => (
                <tr
                  key={k}
                  className={cn(
                    "border-b border-zinc-800/60",
                    i % 2 === 0 ? "bg-zinc-900" : "bg-zinc-900/40",
                  )}
                >
                  <td className="w-40 px-4 py-2.5 font-medium text-zinc-400 align-top">
                    {prettifyKey(k)}
                  </td>
                  <td className="px-4 py-2.5 text-zinc-200 break-all">
                    {renderScalar(v)}
                  </td>
                </tr>
              ))}
              {Object.keys(scalars).length === 0 && (
                <tr>
                  <td colSpan={2} className="px-4 py-6 text-center text-zinc-500">
                    No scalar fields
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {/* Collection tabs: header + rows */}
      {activeTab !== "__info__" && collections[activeTab] && (
        <CollectionTable rows={collections[activeTab]} />
      )}
    </div>
  )
}

function CollectionTable({ rows }: { rows: unknown[][] }) {
  if (rows.length === 0) {
    return (
      <p className="px-4 py-6 text-center text-xs text-zinc-500">No data</p>
    )
  }

  const [header, ...body] = rows

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-xs">
        <thead>
          <tr className="border-b border-zinc-800 bg-zinc-800/60">
            {(header as string[]).map((h, i) => (
              <th
                key={i}
                className="px-4 py-2.5 text-left font-semibold text-zinc-300 whitespace-nowrap"
              >
                {prettifyKey(String(h))}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {body.map((row, ri) => (
            <tr
              key={ri}
              className={cn(
                "border-b border-zinc-800/40",
                ri % 2 === 0 ? "bg-zinc-900" : "bg-zinc-900/40",
              )}
            >
              {(row as unknown[]).map((cell, ci) => (
                <td key={ci} className="px-4 py-2 text-zinc-300 whitespace-nowrap">
                  {renderScalar(cell)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

// ─── Helpers ────────────────────────────────────────────────────────────────

function renderScalar(v: unknown): string {
  if (v === null || v === undefined) return "—"
  if (typeof v === "boolean") return v ? "Yes" : "No"
  return String(v)
}

function prettifyKey(k: string): string {
  return k.replace(/_/g, " ").replace(/\./g, " › ").replace(/\b\w/g, (c) => c.toUpperCase())
}

type FlatResult = {
  scalars: Record<string, unknown>
  collections: Record<string, unknown[][]>
}

function flattenForDisplay(data: Record<string, unknown>): FlatResult {
  const scalars: Record<string, unknown> = {}
  const collections: Record<string, unknown[][]> = {}

  for (const [key, value] of Object.entries(data)) {
    if (!Array.isArray(value) && typeof value !== "object") {
      scalars[key] = value
      continue
    }

    if (Array.isArray(value)) {
      // Special tables structure
      if (key === "tables" && isTablesStructure(value)) {
        value.forEach((table: any, i: number) => {
          const title = table.title ?? `Table ${i + 1}`
          const headers = table.headers ?? []
          const rows = table.rows ?? []
          collections[title] = [headers, ...rows]
        })
        continue
      }

      // Array of objects → collection
      if (isArrayOfObjects(value)) {
        const headers = mergeKeys(value as Record<string, unknown>[])
        const rows = (value as Record<string, unknown>[]).map((item) =>
          headers.map((h) => (typeof item[h] === "object" ? JSON.stringify(item[h]) : item[h]))
        )
        collections[key] = [headers, ...rows]
        continue
      }

      // Array of scalars → comma-joined
      scalars[key] = (value as unknown[]).map(String).join(", ")
      continue
    }

    // Nested object → dot-notation scalars
    if (value && typeof value === "object") {
      flattenObject(value as Record<string, unknown>, key, scalars)
    }
  }

  return { scalars, collections }
}

function flattenObject(
  obj: Record<string, unknown>,
  prefix: string,
  out: Record<string, unknown>,
): void {
  for (const [k, v] of Object.entries(obj)) {
    const key = `${prefix}.${k}`
    if (v && typeof v === "object" && !Array.isArray(v)) {
      flattenObject(v as Record<string, unknown>, key, out)
    } else {
      out[key] = v
    }
  }
}

function isArrayOfObjects(arr: unknown[]): boolean {
  return arr.length > 0 && arr.some((i) => typeof i === "object" && i !== null && !Array.isArray(i))
}

function isTablesStructure(arr: unknown[]): boolean {
  return arr.length > 0 && (arr[0] as any)?.rows !== undefined
}

function mergeKeys(items: Record<string, unknown>[]): string[] {
  const seen = new Set<string>()
  items.forEach((item) => Object.keys(item).forEach((k) => seen.add(k)))
  return Array.from(seen)
}
