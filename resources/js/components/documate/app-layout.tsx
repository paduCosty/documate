"use client"

import { Link } from '@inertiajs/react';

import { usePathname } from "next/navigation"
import { LayoutDashboard, FolderOpen, BarChart2, CreditCard, Settings, LogOut } from "lucide-react"
import { Logo } from "./logo"
import { cn } from "@/lib/utils"

interface AppLayoutProps {
  children: React.ReactNode
  user?: {
    name: string
    email: string
    plan: "free" | "pro" | "business"
  }
}

const mainNavItems = [
  { name: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
  { name: "My Files", href: "/dashboard/files", icon: FolderOpen },
  { name: "Usage", href: "/dashboard/usage", icon: BarChart2 },
]

const accountNavItems = [
  { name: "Billing", href: "/dashboard/billing", icon: CreditCard },
  { name: "Settings", href: "/dashboard/settings", icon: Settings },
]

const planBadgeStyles = {
  free: "bg-zinc-800 text-zinc-400",
  pro: "bg-white text-black",
  business: "bg-amber-500 text-black",
}

export function AppLayout({ children, user = { name: "Alex Johnson", email: "alex@example.com", plan: "free" } }: AppLayoutProps) {
  const pathname = usePathname()

  return (
    <div className="flex min-h-screen bg-zinc-950">
      {/* Sidebar */}
      <aside className="sticky top-0 hidden h-screen w-60 flex-shrink-0 border-r border-zinc-800 bg-zinc-950 md:block">
        <div className="flex h-full flex-col">
          {/* Logo */}
          <div className="flex h-16 items-center px-6">
            <Logo />
          </div>

          {/* Navigation */}
          <nav className="flex-1 px-3 py-4">
            <div className="space-y-6">
              {/* Main Section */}
              <div>
                <div className="mb-2 px-3 text-xs font-medium uppercase tracking-widest text-zinc-600">
                  Main
                </div>
                <div className="space-y-1">
                  {mainNavItems.map((item) => {
                    const isActive = pathname === item.href
                    return (
                      <Link
                        key={item.name}
                        href={item.href}
                        className={cn(
                          "flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition-colors",
                          isActive
                            ? "bg-zinc-800 text-white"
                            : "text-zinc-500 hover:bg-zinc-800/50 hover:text-white"
                        )}
                      >
                        <item.icon className="h-4 w-4" />
                        {item.name}
                      </Link>
                    )
                  })}
                </div>
              </div>

              {/* Account Section */}
              <div>
                <div className="mb-2 px-3 text-xs font-medium uppercase tracking-widest text-zinc-600">
                  Account
                </div>
                <div className="space-y-1">
                  {accountNavItems.map((item) => {
                    const isActive = pathname === item.href
                    return (
                      <Link
                        key={item.name}
                        href={item.href}
                        className={cn(
                          "flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition-colors",
                          isActive
                            ? "bg-zinc-800 text-white"
                            : "text-zinc-500 hover:bg-zinc-800/50 hover:text-white"
                        )}
                      >
                        <item.icon className="h-4 w-4" />
                        {item.name}
                      </Link>
                    )
                  })}
                </div>
              </div>
            </div>
          </nav>

          {/* User Card */}
          <div className="border-t border-zinc-800 p-3">
            <div className="flex items-center gap-3 rounded-xl p-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-800 text-sm font-semibold text-white">
                {user.name.split(" ").map(n => n[0]).join("")}
              </div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2">
                  <span className="truncate text-sm text-white">{user.name}</span>
                  <span className={cn("rounded-full px-2 py-0.5 text-xs font-medium capitalize", planBadgeStyles[user.plan])}>
                    {user.plan}
                  </span>
                </div>
                <span className="text-xs text-zinc-500">{user.email}</span>
              </div>
              <button className="p-1 text-zinc-600 transition-colors hover:text-white">
                <LogOut className="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1">
        {children}
      </main>
    </div>
  )
}
