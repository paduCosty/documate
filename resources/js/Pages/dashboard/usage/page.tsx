"use client"

import { useState } from "react"
import { GitMerge, Minimize2, FileText, Scissors, Image, Lock } from "lucide-react"
import { AppLayout } from "@/components/documate/app-layout"
import { DocumateCard } from "@/components/documate/documate-card"

const usageCards = [
  { title: "Daily Operations", current: 3, limit: 10, lastUsed: "2 hours ago" },
  { title: "Monthly Files", current: 47, limit: 100, lastUsed: "Today" },
  { title: "Storage Used", current: 24, limit: 100, unit: "MB", lastUsed: "Today" },
  { title: "API Calls", current: 0, limit: 0, lastUsed: "Never", locked: true },
]

const toolBreakdown = [
  { name: "Merge PDF", icon: GitMerge, count: 12, color: "bg-blue-500", maxCount: 15 },
  { name: "Compress PDF", icon: Minimize2, count: 8, color: "bg-green-500", maxCount: 15 },
  { name: "Convert", icon: FileText, count: 15, color: "bg-purple-500", maxCount: 15 },
  { name: "Split PDF", icon: Scissors, count: 5, color: "bg-orange-500", maxCount: 15 },
  { name: "PDF to JPG", icon: Image, count: 7, color: "bg-pink-500", maxCount: 15 },
]

const periods = ["This month", "Last month", "Last 3 months"]

export default function UsagePage() {
  const [selectedPeriod, setSelectedPeriod] = useState("This month")

  return (
    <AppLayout>
      <div className="px-8 py-10">
        <h2 className="text-2xl font-semibold text-white">Usage</h2>

        {/* Period Selector */}
        <div className="mt-6 flex gap-2">
          {periods.map((period) => (
            <button
              key={period}
              onClick={() => setSelectedPeriod(period)}
              className={`rounded-xl px-4 py-2 text-sm transition-colors ${
                selectedPeriod === period
                  ? "bg-zinc-800 text-white"
                  : "text-zinc-500 hover:text-white"
              }`}
            >
              {period}
            </button>
          ))}
        </div>

        {/* Usage Cards */}
        <div className="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2">
          {usageCards.map((card) => (
            <DocumateCard key={card.title} className={card.locked ? "opacity-60" : ""}>
              <div className="flex items-start justify-between">
                <h3 className="font-semibold text-white">{card.title}</h3>
                {card.locked && <Lock className="h-4 w-4 text-zinc-600" />}
              </div>
              <div className="mt-4 flex items-baseline gap-1">
                <span className="text-3xl font-bold text-white">{card.current}</span>
                {card.limit > 0 && (
                  <span className="text-lg text-zinc-500">/ {card.limit}{card.unit ? ` ${card.unit}` : ""}</span>
                )}
                {card.locked && <span className="text-zinc-600">Business only</span>}
              </div>
              {!card.locked && card.limit > 0 && (
                <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-zinc-800">
                  <div
                    className="h-full rounded-full bg-white"
                    style={{ width: `${(card.current / card.limit) * 100}%` }}
                  />
                </div>
              )}
              <p className="mt-3 text-xs text-zinc-600">Last used: {card.lastUsed}</p>
            </DocumateCard>
          ))}
        </div>

        {/* Operations Breakdown */}
        <DocumateCard className="mt-4">
          <h3 className="font-semibold text-white">Operations by tool</h3>
          <div className="mt-6 space-y-4">
            {toolBreakdown.map((tool) => (
              <div key={tool.name} className="flex items-center gap-4">
                <div className="flex w-32 items-center gap-2">
                  <tool.icon className="h-4 w-4 text-zinc-500" />
                  <span className="text-sm text-zinc-400">{tool.name}</span>
                </div>
                <div className="flex-1">
                  <div className="h-1 overflow-hidden rounded-full bg-zinc-800">
                    <div
                      className={`h-full rounded-full ${tool.color}`}
                      style={{ width: `${(tool.count / tool.maxCount) * 100}%` }}
                    />
                  </div>
                </div>
                <span className="w-8 text-right text-sm text-zinc-400">{tool.count}</span>
              </div>
            ))}
          </div>
        </DocumateCard>
      </div>
    </AppLayout>
  )
}
