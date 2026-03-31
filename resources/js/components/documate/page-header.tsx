import { Link } from '@inertiajs/react';

import { ChevronRight } from "lucide-react"

interface BreadcrumbItem {
  label: string
  href?: string
}

interface PageHeaderProps {
  breadcrumbs?: BreadcrumbItem[]
  title: string
  subtitle?: string
}

export function PageHeader({ breadcrumbs, title, subtitle }: PageHeaderProps) {
  return (
    <div>
      {breadcrumbs && breadcrumbs.length > 0 && (
        <nav className="mb-4 flex items-center gap-1 text-xs">
          {breadcrumbs.map((item, index) => (
            <span key={index} className="flex items-center gap-1">
              {index > 0 && <ChevronRight className="h-3 w-3 text-zinc-600" />}
              {item.href ? (
                <Link href={item.href} className="text-zinc-600 transition-colors hover:text-white">
                  {item.label}
                </Link>
              ) : (
                <span className="text-zinc-400">{item.label}</span>
              )}
            </span>
          ))}
        </nav>
      )}
      <h1 className="text-3xl font-bold tracking-tight text-white">{title}</h1>
      {subtitle && <p className="mt-2 text-zinc-500">{subtitle}</p>}
    </div>
  )
}
