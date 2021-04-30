Credit Enrolment for Moodle
==========

by bdecent.de

While there are existing solutions to sell courses in moodle, they only cover the requirements to a certain extent: Some of the solutions (e.g. the PayPal and Stripe extensions) work great when courses are sold to individuals. Other solutions (e.g. integrations with courseware, woocommerce etc.) are much more feature rich and provide the full e-commerce experience, including seo, many billing options, invoicing, discounts etc. However, the implementation, maintenance and running cost of such a solution is a lot higher.

Our solution targets medium sized companies who want to sell their courses to business clients (instead of individuals) by introducing a very simple credit system. Business clients can buy a certain number of credits using the existing payment/invoicing processes already in place at all customers (not within moodle). These credits can be assigned to users in moodle (manually or via upload) and then used to “buy” courses. Thus, implementation is basically done in minutes.

Credit enrolment is built as a lightweight enrolment plugin and is available for free on moodle.org/plugins.

The key features are:
- store credits in the user’s profile
- assign a price (=number of credits required) for a course
- enrol into the course by deducting the required credits from the user who enrols (only if enough credits are available)

There's also a companion availability condition which allows teachers to restrict course contents (instead of the course itself). Students need to pay the configured amount of credits to access the activity or resource.
