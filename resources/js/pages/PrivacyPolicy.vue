<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
</script>

<template>
  <Head title="Privacy Policy - ServeWise" />

  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-10">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-xl md:text-2xl font-bold text-gray-900">ServeWise</h1>
          <Link href="/register">
            <Button variant="ghost" size="sm">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Registration
            </Button>
          </Link>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
      <div class="bg-white rounded-lg shadow-sm border p-6 md:p-8">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">Privacy Policy</h1>
        <p class="text-sm text-gray-500 mb-8">Last updated: {{ new Date().toLocaleDateString() }}</p>

        <div class="prose prose-sm md:prose-base max-w-none space-y-6">
          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">1. Introduction</h2>
            <p class="text-gray-700 leading-relaxed">
              ServeWise ("we", "us", or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect,
              use, disclose, and safeguard your information when you use our restaurant management system ("the Service").
              Please read this policy carefully.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">2. Information We Collect</h2>
            <h3 class="text-lg font-medium text-gray-900 mb-2 mt-4">2.1 Personal Information</h3>
            <p class="text-gray-700 leading-relaxed mb-3">
              When you register for an account, we collect:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
              <li>Name (first, middle, last)</li>
              <li>Email address</li>
              <li>Date of birth</li>
              <li>Gender</li>
              <li>Phone number</li>
              <li>Restaurant information (name, address, contact details)</li>
            </ul>

            <h3 class="text-lg font-medium text-gray-900 mb-2 mt-4">2.2 Business Data</h3>
            <p class="text-gray-700 leading-relaxed mb-3">
              When using our Service, we store:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
              <li>Menu items and ingredients</li>
              <li>Customer orders and transactions</li>
              <li>Employee information</li>
              <li>Supplier details and purchase orders</li>
              <li>Inventory data</li>
              <li>Financial reports and analytics</li>
            </ul>

            <h3 class="text-lg font-medium text-gray-900 mb-2 mt-4">2.3 Usage Information</h3>
            <p class="text-gray-700 leading-relaxed">
              We automatically collect information about how you use the Service, including IP addresses, browser type,
              device information, pages visited, and timestamps.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">3. How We Use Your Information</h2>
            <p class="text-gray-700 leading-relaxed mb-3">
              We use the collected information to:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
              <li>Provide, operate, and maintain the Service</li>
              <li>Process your transactions and manage subscriptions</li>
              <li>Send you account-related notifications and updates</li>
              <li>Provide customer support and respond to inquiries</li>
              <li>Improve and optimize the Service</li>
              <li>Monitor and analyze usage patterns</li>
              <li>Detect, prevent, and address technical issues or fraud</li>
              <li>Comply with legal obligations</li>
            </ul>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">4. Information Sharing and Disclosure</h2>
            <p class="text-gray-700 leading-relaxed mb-3">
              We do not sell your personal information. We may share your information only in the following circumstances:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
              <li><strong>With your consent:</strong> When you explicitly authorize us to share information</li>
              <li><strong>Service providers:</strong> With third-party vendors who help us operate the Service (e.g., payment processors, email services)</li>
              <li><strong>Business transfers:</strong> In connection with a merger, acquisition, or sale of assets</li>
              <li><strong>Legal requirements:</strong> When required by law or to protect our rights and safety</li>
              <li><strong>Aggregated data:</strong> We may share anonymized, aggregated data that cannot identify you</li>
            </ul>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">5. Data Security</h2>
            <p class="text-gray-700 leading-relaxed">
              We implement appropriate technical and organizational measures to protect your information against unauthorized access,
              alteration, disclosure, or destruction. These measures include encryption, secure servers, access controls, and regular security audits.
              However, no method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">6. Data Retention</h2>
            <p class="text-gray-700 leading-relaxed">
              We retain your personal information for as long as necessary to provide the Service and fulfill the purposes outlined in this Privacy Policy.
              When you close your account, we will delete or anonymize your personal information within a reasonable timeframe, except where we are
              required to retain it for legal or regulatory purposes.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">7. Your Rights and Choices</h2>
            <p class="text-gray-700 leading-relaxed mb-3">
              You have the following rights regarding your personal information:
            </p>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
              <li><strong>Access:</strong> Request a copy of the personal information we hold about you</li>
              <li><strong>Correction:</strong> Update or correct inaccurate information</li>
              <li><strong>Deletion:</strong> Request deletion of your personal information</li>
              <li><strong>Export:</strong> Request a copy of your data in a portable format</li>
              <li><strong>Opt-out:</strong> Unsubscribe from marketing communications</li>
              <li><strong>Object:</strong> Object to certain processing activities</li>
            </ul>
            <p class="text-gray-700 leading-relaxed mt-3">
              To exercise these rights, please contact us at support@servewise.com.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">8. Cookies and Tracking Technologies</h2>
            <p class="text-gray-700 leading-relaxed">
              We use cookies and similar tracking technologies to enhance your experience, analyze usage, and remember your preferences.
              You can control cookies through your browser settings. However, disabling cookies may affect the functionality of the Service.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">9. Third-Party Links</h2>
            <p class="text-gray-700 leading-relaxed">
              The Service may contain links to third-party websites or services. We are not responsible for the privacy practices of these third parties.
              We encourage you to review their privacy policies before providing any personal information.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">10. Children's Privacy</h2>
            <p class="text-gray-700 leading-relaxed">
              The Service is not intended for children under 13 years of age. We do not knowingly collect personal information from children.
              If we become aware that a child has provided us with personal information, we will delete it immediately.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">11. International Data Transfers</h2>
            <p class="text-gray-700 leading-relaxed">
              Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards
              are in place to protect your information in accordance with this Privacy Policy and applicable laws.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">12. Changes to This Privacy Policy</h2>
            <p class="text-gray-700 leading-relaxed">
              We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new Privacy Policy
              on this page and updating the "Last updated" date. Your continued use of the Service after such changes constitutes acceptance
              of the updated Privacy Policy.
            </p>
          </section>

          <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">13. Contact Us</h2>
            <p class="text-gray-700 leading-relaxed">
              If you have any questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us at:
              <br><br>
              <strong>Email:</strong> support@servewise.com<br>
              <strong>Website:</strong> www.servewise.com<br>
              <strong>Address:</strong> [Your Company Address]
            </p>
          </section>
        </div>

        <div class="mt-8 pt-6 border-t">
          <Link href="/register">
            <Button variant="outline">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Registration
            </Button>
          </Link>
        </div>
      </div>
    </main>
  </div>
</template>
