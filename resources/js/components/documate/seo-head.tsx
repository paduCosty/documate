import { Head } from '@inertiajs/react'

interface SEOHeadProps {
  title: string
  description: string
  canonical?: string  // accepts full URL or just a path like "/tools/merge-pdf"
}

export function SEOHead({ title, description, canonical }: SEOHeadProps) {
  const base = typeof window !== 'undefined' ? window.location.origin : ''
  const ogImage = `${base}/og-image.png`
  const canonicalUrl = canonical
    ? canonical.startsWith('http') ? canonical : `${base}${canonical}`
    : undefined

  return (
    <Head>
      <title>{title}</title>
      <meta head-key="description" name="description" content={description} />
      <meta head-key="og:title" property="og:title" content={title} />
      <meta head-key="og:description" property="og:description" content={description} />
      <meta head-key="og:type" property="og:type" content="website" />
      <meta head-key="og:site_name" property="og:site_name" content="Documate" />
      <meta head-key="og:image" property="og:image" content={ogImage} />
      <meta head-key="og:image:width" property="og:image:width" content="1200" />
      <meta head-key="og:image:height" property="og:image:height" content="630" />
      {canonicalUrl && <meta head-key="og:url" property="og:url" content={canonicalUrl} />}
      {canonicalUrl && <link head-key="canonical" rel="canonical" href={canonicalUrl} />}
      <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
      <meta head-key="twitter:title" name="twitter:title" content={title} />
      <meta head-key="twitter:description" name="twitter:description" content={description} />
      <meta head-key="twitter:image" name="twitter:image" content={ogImage} />
    </Head>
  )
}
