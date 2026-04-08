"use client"

import { useState, useEffect } from "react"
import { Camera, Monitor, Bell, BarChart2, Megaphone, ShieldAlert } from "lucide-react"
import { AppLayout } from "@/components/documate/app-layout"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateButton } from "@/components/documate/documate-button"
import { DocumateInput } from "@/components/documate/documate-input"
import { Switch } from "@/components/ui/switch"
import { useForm, usePage } from "@inertiajs/react"
import {
  AlertDialog, AlertDialogCancel, AlertDialogContent,
  AlertDialogDescription, AlertDialogFooter, AlertDialogHeader,
  AlertDialogTitle, AlertDialogTrigger,
} from "@/components/ui/alert-dialog"

const tabs = ["Profile", "Security", "Notifications", "Danger Zone"]

const notificationDefs = [
  { id: "email",    title: "Email notifications", description: "Receive email updates about your files",      icon: Bell,       locked: false },
  { id: "weekly",   title: "Weekly summary",       description: "Get a weekly summary of your usage",         icon: BarChart2,  locked: false },
  { id: "product",  title: "Product updates",      description: "Be the first to know about new features",    icon: Megaphone,  locked: false },
  { id: "security", title: "Security alerts",      description: "Important security notifications",           icon: ShieldAlert,locked: true  },
]

type PageProps = {
  auth: { user: { name: string; email: string; email_verified_at: string | null } }
  status?: string
  isSocialUser: boolean
  socialProvider?: string
  notificationSettings: Record<string, boolean>
}

export default function SettingsPage() {
  const { auth, status, isSocialUser, socialProvider, notificationSettings: initialNotifs } = usePage<{ props: PageProps }>().props as unknown as PageProps
  const user = auth?.user

  const [activeTab, setActiveTab]           = useState("Profile")
  const [passwordStrength, setPasswordStrength] = useState(0)
  const [deleteConfirmText, setDeleteConfirmText] = useState("")
  const [successMsg, setSuccessMsg]         = useState<string | null>(null)
  const [notifSettings, setNotifSettings]   = useState(initialNotifs)

  const profileForm = useForm({ name: user?.name || "", email: user?.email || "" })
  const passwordForm = useForm({ current_password: "", password: "", password_confirmation: "" })
  const notifForm = useForm(initialNotifs)
  const deleteForm = useForm({ password: "" })

  useEffect(() => {
    if (status === "profile-updated")      setSuccessMsg("Profile updated successfully.")
    if (status === "password-updated")     setSuccessMsg("Password updated successfully.")
    if (status === "notifications-updated") setSuccessMsg("Notification preferences saved.")
    if (status) { const t = setTimeout(() => setSuccessMsg(null), 3000); return () => clearTimeout(t) }
  }, [status])

  const handlePasswordStrength = (value: string) => {
    let s = 0
    if (value.length >= 8) s++
    if (/[A-Z]/.test(value)) s++
    if (/[0-9]/.test(value)) s++
    if (/[^A-Za-z0-9]/.test(value)) s++
    setPasswordStrength(s)
  }

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    profileForm.patch("/profile")
  }

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    passwordForm.put("/password", { onSuccess: () => { passwordForm.reset(); setPasswordStrength(0) } })
  }

  const handleNotifChange = (id: string, value: boolean) => {
    const updated = { ...notifSettings, [id]: value }
    setNotifSettings(updated)
    notifForm.setData(updated as any)
    notifForm.patch("/profile/notifications")
  }

  const handleDeleteAccount = (e: React.FormEvent) => {
    e.preventDefault()
    if (deleteConfirmText !== "DELETE") return
    deleteForm.delete("/profile")
  }

  const strengthColors = ["bg-red-500", "bg-orange-500", "bg-yellow-500", "bg-green-500"]
  const strengthLabels = ["Weak", "Fair", "Good", "Strong"]
  const canDelete = deleteConfirmText === "DELETE" && (isSocialUser || deleteForm.data.password.length > 0)

  return (
    <AppLayout>
      <div className="px-8 py-10">
        <h2 className="text-2xl font-semibold text-white">Settings</h2>

        {successMsg && (
          <div className="mt-4 rounded-xl border border-green-700/50 bg-green-950/30 px-4 py-3 text-sm text-green-400">
            {successMsg}
          </div>
        )}

        {/* Tabs */}
        <div className="mt-6 border-b border-zinc-800">
          <div className="flex gap-6">
            {tabs.map((tab) => (
              <button key={tab} onClick={() => setActiveTab(tab)}
                className={`border-b-2 pb-3 text-sm transition-colors ${activeTab === tab ? "border-white text-white" : "border-transparent text-zinc-500 hover:text-white"}`}>
                {tab}
              </button>
            ))}
          </div>
        </div>

        {/* Profile Tab */}
        {activeTab === "Profile" && (
          <div className="mt-8">
            <div className="flex items-center gap-4">
              <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-zinc-800 text-2xl font-bold text-white">
                {user?.name?.split(" ").map((n: string) => n[0]).join("") || "U"}
              </div>
              {isSocialUser && socialProvider && (
                <DocumateBadge variant="success" className="capitalize">{socialProvider} account</DocumateBadge>
              )}
            </div>

            <form onSubmit={handleProfileSubmit} className="mt-8 max-w-md space-y-5">
              <DocumateInput label="Full name" value={profileForm.data.name}
                onChange={(e) => profileForm.setData("name", e.target.value)}
                error={profileForm.errors.name} />
              <div>
                <DocumateInput label="Email" value={profileForm.data.email}
                  onChange={(e) => profileForm.setData("email", e.target.value)}
                  error={profileForm.errors.email} />
                <div className="mt-1.5 flex items-center gap-2">
                  {user?.email_verified_at
                    ? <DocumateBadge variant="success">Verified</DocumateBadge>
                    : <DocumateBadge>Unverified</DocumateBadge>}
                </div>
              </div>
              <DocumateButton disabled={profileForm.processing}>
                {profileForm.processing ? "Saving..." : "Save changes"}
              </DocumateButton>
            </form>
          </div>
        )}

        {/* Security Tab */}
        {activeTab === "Security" && (
          <div className="mt-8">
            <div className="max-w-md">
              <h3 className="text-lg font-semibold text-white">Change password</h3>

              {isSocialUser && !user ? (
                <p className="mt-4 text-sm text-zinc-500">
                  You signed in with {socialProvider}. Set a password below to also enable email/password login.
                </p>
              ) : null}

              <form onSubmit={handlePasswordSubmit} className="mt-6 space-y-4">
                {!isSocialUser && (
                  <DocumateInput label="Current password" type="password"
                    value={passwordForm.data.current_password}
                    onChange={(e) => passwordForm.setData("current_password", e.target.value)}
                    error={passwordForm.errors.current_password} />
                )}
                <div>
                  <DocumateInput label="New password" type="password"
                    value={passwordForm.data.password}
                    onChange={(e) => { passwordForm.setData("password", e.target.value); handlePasswordStrength(e.target.value) }}
                    error={passwordForm.errors.password} />
                  <div className="mt-2 flex gap-1">
                    {[0,1,2,3].map((i) => (
                      <div key={i} className={`h-1 flex-1 rounded-full ${i < passwordStrength ? strengthColors[passwordStrength - 1] : "bg-zinc-800"}`} />
                    ))}
                  </div>
                  {passwordStrength > 0 && <p className="mt-1 text-xs text-zinc-500">{strengthLabels[passwordStrength - 1]}</p>}
                </div>
                <DocumateInput label="Confirm password" type="password"
                  value={passwordForm.data.password_confirmation}
                  onChange={(e) => passwordForm.setData("password_confirmation", e.target.value)}
                  error={passwordForm.errors.password_confirmation} />
                <DocumateButton disabled={passwordForm.processing}>
                  {passwordForm.processing ? "Updating..." : "Update password"}
                </DocumateButton>
              </form>
            </div>

            <div className="mt-12">
              <h3 className="text-lg font-semibold text-white">Current session</h3>
              <div className="mt-4">
                <DocumateCard padding="sm" className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Monitor className="h-5 w-5 text-zinc-500" />
                    <div>
                      <div className="flex items-center gap-2">
                        <span className="text-sm text-white">This device</span>
                        <DocumateBadge variant="success">Current</DocumateBadge>
                      </div>
                      <span className="text-xs text-zinc-500">Active now</span>
                    </div>
                  </div>
                </DocumateCard>
              </div>
            </div>
          </div>
        )}

        {/* Notifications Tab */}
        {activeTab === "Notifications" && (
          <div className="mt-8 space-y-2">
            {notificationDefs.map((n) => (
              <DocumateCard key={n.id} padding="sm" className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <n.icon className="h-5 w-5 text-zinc-500" />
                  <div>
                    <span className="text-sm font-medium text-white">{n.title}</span>
                    <p className="text-xs text-zinc-500">{n.description}</p>
                  </div>
                </div>
                <Switch
                  checked={notifSettings[n.id] ?? false}
                  onCheckedChange={(checked) => !n.locked && handleNotifChange(n.id, checked)}
                  disabled={n.locked}
                  className="data-[state=checked]:bg-white" />
              </DocumateCard>
            ))}
          </div>
        )}

        {/* Danger Zone Tab */}
        {activeTab === "Danger Zone" && (
          <div className="mt-8">
            <DocumateCard className="border-red-900/40 bg-red-950/10">
              <h3 className="text-lg font-semibold text-red-400">Danger Zone</h3>
              <div className="mt-4 flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                <div>
                  <span className="font-medium text-white">Delete account</span>
                  <p className="mt-1 text-sm text-zinc-500">
                    Permanently delete your account and all associated data. This cannot be undone.
                  </p>
                </div>
                <AlertDialog>
                  <AlertDialogTrigger asChild>
                    <DocumateButton variant="destructive" size="sm">Delete account</DocumateButton>
                  </AlertDialogTrigger>
                  <AlertDialogContent className="border-zinc-800 bg-zinc-900">
                    <AlertDialogHeader>
                      <AlertDialogTitle className="text-white">Delete your account?</AlertDialogTitle>
                      <AlertDialogDescription className="text-zinc-400">
                        This will permanently delete all your files, history, and cancel any active subscription.
                        This action <span className="text-white font-medium">cannot be undone</span>.
                      </AlertDialogDescription>
                    </AlertDialogHeader>
                    <form onSubmit={handleDeleteAccount} className="mt-2 space-y-4">
                      <div>
                        <label className="mb-1.5 block text-xs text-zinc-400">
                          Type <span className="font-mono text-red-400">DELETE</span> to confirm
                        </label>
                        <input type="text" value={deleteConfirmText}
                          onChange={(e) => setDeleteConfirmText(e.target.value)} placeholder="DELETE"
                          className="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-2.5 text-sm text-white placeholder:text-zinc-600 focus:border-red-900 focus:outline-none" />
                      </div>
                      {!isSocialUser && (
                        <div>
                          <label className="mb-1.5 block text-xs text-zinc-400">Your password</label>
                          <input type="password" value={deleteForm.data.password}
                            onChange={(e) => deleteForm.setData("password", e.target.value)}
                            placeholder="Enter your password"
                            className="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-2.5 text-sm text-white placeholder:text-zinc-600 focus:border-red-900 focus:outline-none" />
                          {deleteForm.errors.password && <p className="mt-1 text-xs text-red-400">{deleteForm.errors.password}</p>}
                        </div>
                      )}
                      <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => { setDeleteConfirmText(""); deleteForm.reset() }}
                          className="border-zinc-700 bg-transparent text-zinc-400 hover:bg-zinc-800 hover:text-white">
                          Cancel
                        </AlertDialogCancel>
                        <button type="submit" disabled={!canDelete || deleteForm.processing}
                          className="rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                          {deleteForm.processing ? "Deleting..." : "Delete permanently"}
                        </button>
                      </AlertDialogFooter>
                    </form>
                  </AlertDialogContent>
                </AlertDialog>
              </div>
            </DocumateCard>
          </div>
        )}
      </div>
    </AppLayout>
  )
}
