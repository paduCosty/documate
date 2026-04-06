"use client"

import { useState } from "react"
import { Camera, Monitor, Smartphone, Bell, BarChart2, Megaphone, ShieldAlert } from "lucide-react"
import { AppLayout } from "@/components/documate/app-layout"
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateButton } from "@/components/documate/documate-button"
import { DocumateInput } from "@/components/documate/documate-input"
import { Switch } from "@/components/ui/switch"
import { useForm, usePage } from "@inertiajs/react"
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog"

const tabs = ["Profile", "Security", "Notifications", "Danger Zone"]

const sessions = [
  { id: 1, device: "Chrome on macOS", location: "London, UK", time: "2 hours ago", icon: Monitor, current: true },
  { id: 2, device: "Safari on iPhone", location: "London, UK", time: "Yesterday", icon: Smartphone, current: false },
  { id: 3, device: "Firefox on Windows", location: "Manchester, UK", time: "3 days ago", icon: Monitor, current: false },
]

const notificationDefs = [
  { id: "email", title: "Email notifications", description: "Receive email updates about your files", icon: Bell, enabled: true, locked: false },
  { id: "weekly", title: "Weekly summary", description: "Get a weekly summary of your usage", icon: BarChart2, enabled: true, locked: false },
  { id: "product", title: "Product updates", description: "Be the first to know about new features", icon: Megaphone, enabled: false, locked: false },
  { id: "security", title: "Security alerts", description: "Important security notifications", icon: ShieldAlert, enabled: true, locked: true },
]

export default function SettingsPage() {
  const { auth } = usePage().props as any
  const user = auth?.user

  const [activeTab, setActiveTab] = useState("Profile")
  const [passwordStrength, setPasswordStrength] = useState(0)
  const [deleteConfirmText, setDeleteConfirmText] = useState("")
  const [notificationSettings, setNotificationSettings] = useState(
    Object.fromEntries(notificationDefs.map((n) => [n.id, n.enabled]))
  )

  const profileForm = useForm({
    name: user?.name || "",
    email: user?.email || "",
  })

  const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
  })

  const deleteForm = useForm({
    password: "",
  })

  const handlePasswordChange = (value: string) => {
    let strength = 0
    if (value.length >= 8) strength++
    if (/[A-Z]/.test(value)) strength++
    if (/[0-9]/.test(value)) strength++
    if (/[^A-Za-z0-9]/.test(value)) strength++
    setPasswordStrength(strength)
  }

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    profileForm.patch("/profile")
  }

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    passwordForm.put("/password", {
      onSuccess: () => {
        passwordForm.reset()
        setPasswordStrength(0)
      },
    })
  }

  const handleDeleteAccount = (e: React.FormEvent) => {
    e.preventDefault()
    if (deleteConfirmText !== "DELETE") return
    deleteForm.delete("/profile", {
      onSuccess: () => {
        // Redirected to / by controller
      },
    })
  }

  const strengthLabels = ["Weak", "Fair", "Good", "Strong"]
  const strengthColors = ["bg-red-500", "bg-orange-500", "bg-yellow-500", "bg-green-500"]

  const canDelete = deleteConfirmText === "DELETE" && deleteForm.data.password.length > 0

  return (
    <AppLayout>
      <div className="px-8 py-10">
        <h2 className="text-2xl font-semibold text-white">Settings</h2>

        {/* Tabs */}
        <div className="mt-6 border-b border-zinc-800">
          <div className="flex gap-6">
            {tabs.map((tab) => (
              <button
                key={tab}
                onClick={() => setActiveTab(tab)}
                className={`border-b-2 pb-3 text-sm transition-colors ${
                  activeTab === tab
                    ? "border-white text-white"
                    : "border-transparent text-zinc-500 hover:text-white"
                }`}
              >
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
              <DocumateButton variant="ghost" size="sm">
                <Camera className="h-4 w-4" />
                Upload photo
              </DocumateButton>
            </div>

            <form onSubmit={handleProfileSubmit} className="mt-8 max-w-md space-y-5">
              <DocumateInput
                label="Full name"
                value={profileForm.data.name}
                onChange={(e) => profileForm.setData("name", e.target.value)}
                error={profileForm.errors.name}
              />
              <div>
                <DocumateInput
                  label="Email"
                  value={profileForm.data.email}
                  onChange={(e) => profileForm.setData("email", e.target.value)}
                  error={profileForm.errors.email}
                />
                <div className="mt-1.5 flex items-center gap-2">
                  <DocumateBadge variant="success">Verified</DocumateBadge>
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
              <form onSubmit={handlePasswordSubmit} className="mt-6 space-y-4">
                <DocumateInput
                  label="Current password"
                  type="password"
                  value={passwordForm.data.current_password}
                  onChange={(e) => passwordForm.setData("current_password", e.target.value)}
                  error={passwordForm.errors.current_password}
                />
                <div>
                  <DocumateInput
                    label="New password"
                    type="password"
                    value={passwordForm.data.password}
                    onChange={(e) => {
                      passwordForm.setData("password", e.target.value)
                      handlePasswordChange(e.target.value)
                    }}
                    error={passwordForm.errors.password}
                  />
                  <div className="mt-2 flex gap-1">
                    {[0, 1, 2, 3].map((i) => (
                      <div
                        key={i}
                        className={`h-1 flex-1 rounded-full ${
                          i < passwordStrength ? strengthColors[passwordStrength - 1] : "bg-zinc-800"
                        }`}
                      />
                    ))}
                  </div>
                  {passwordStrength > 0 && (
                    <p className="mt-1 text-xs text-zinc-500">{strengthLabels[passwordStrength - 1]}</p>
                  )}
                </div>
                <DocumateInput
                  label="Confirm password"
                  type="password"
                  value={passwordForm.data.password_confirmation}
                  onChange={(e) => passwordForm.setData("password_confirmation", e.target.value)}
                  error={passwordForm.errors.password_confirmation}
                />
                <DocumateButton disabled={passwordForm.processing}>
                  {passwordForm.processing ? "Updating..." : "Update password"}
                </DocumateButton>
              </form>
            </div>

            <div className="mt-12">
              <h3 className="text-lg font-semibold text-white">Active sessions</h3>
              <div className="mt-4 space-y-2">
                {sessions.map((session) => (
                  <DocumateCard key={session.id} padding="sm" className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <session.icon className="h-5 w-5 text-zinc-500" />
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-white">{session.device}</span>
                          {session.current && <DocumateBadge variant="success">Current</DocumateBadge>}
                        </div>
                        <span className="text-xs text-zinc-500">{session.location} &middot; {session.time}</span>
                      </div>
                    </div>
                    {!session.current && (
                      <DocumateButton variant="ghost" size="sm" className="text-red-400 hover:text-red-300">
                        Revoke
                      </DocumateButton>
                    )}
                  </DocumateCard>
                ))}
              </div>
              <DocumateButton variant="outline" size="sm" className="mt-4 border-red-900 text-red-400">
                Sign out all other devices
              </DocumateButton>
            </div>
          </div>
        )}

        {/* Notifications Tab */}
        {activeTab === "Notifications" && (
          <div className="mt-8 space-y-2">
            {notificationDefs.map((notification) => (
              <DocumateCard key={notification.id} padding="sm" className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <notification.icon className="h-5 w-5 text-zinc-500" />
                  <div>
                    <span className="text-sm font-medium text-white">{notification.title}</span>
                    <p className="text-xs text-zinc-500">{notification.description}</p>
                  </div>
                </div>
                <Switch
                  checked={notificationSettings[notification.id]}
                  onCheckedChange={(checked) =>
                    !notification.locked &&
                    setNotificationSettings((prev) => ({ ...prev, [notification.id]: checked }))
                  }
                  disabled={notification.locked}
                  className="data-[state=checked]:bg-white"
                />
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
                        <input
                          type="text"
                          value={deleteConfirmText}
                          onChange={(e) => setDeleteConfirmText(e.target.value)}
                          placeholder="DELETE"
                          className="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-2.5 text-sm text-white placeholder:text-zinc-600 focus:border-red-900 focus:outline-none"
                        />
                      </div>
                      <div>
                        <label className="mb-1.5 block text-xs text-zinc-400">Your password</label>
                        <input
                          type="password"
                          value={deleteForm.data.password}
                          onChange={(e) => deleteForm.setData("password", e.target.value)}
                          placeholder="Enter your password"
                          className="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-4 py-2.5 text-sm text-white placeholder:text-zinc-600 focus:border-red-900 focus:outline-none"
                        />
                        {deleteForm.errors.password && (
                          <p className="mt-1 text-xs text-red-400">{deleteForm.errors.password}</p>
                        )}
                      </div>

                      <AlertDialogFooter>
                        <AlertDialogCancel
                          onClick={() => { setDeleteConfirmText(""); deleteForm.reset() }}
                          className="border-zinc-700 bg-transparent text-zinc-400 hover:bg-zinc-800 hover:text-white"
                        >
                          Cancel
                        </AlertDialogCancel>
                        <button
                          type="submit"
                          disabled={!canDelete || deleteForm.processing}
                          className="rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                        >
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
