import { Logo } from "./logo"

interface GuestLayoutProps {
  children: React.ReactNode
}

export function GuestLayout({ children }: GuestLayoutProps) {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-zinc-950 px-6 py-12">
      <div className="mb-8">
        <Logo />
      </div>
      {children}
    </div>
  )
}
