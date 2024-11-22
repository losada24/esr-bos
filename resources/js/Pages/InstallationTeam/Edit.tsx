import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type User, type TypeOfHousing, type InstallationTeam, type TravelCost } from '@/types'
import { installationTeamSchema, type InstallationTeamFormValues } from './InstallationTeamCommon'
import InstallationTeamForm from './InstallationTeamForm'

export default function Edit ({ auth, type_of_housings, users, installation_team, travel_costs }: PageProps & { users: User[], type_of_housings: TypeOfHousing[], installation_team: InstallationTeam, travel_costs: TravelCost []}) {
  const initialValues: InstallationTeamFormValues = {
    id: installation_team.id,
    number_of_member: installation_team.number_of_member,
    worker_compensation_expiration_date: installation_team.worker_compensation_expiration_date,
    liability_expiration_date: installation_team.liability_expiration_date,
    user_id: { value: installation_team.user_id, label: users.find((user: User) => user.id === installation_team.user_id)?.name ?? '' },
    attachments: [],
    type_housing: installation_team.type_housing?.map((typeOfHousing) => {
      return { value: typeOfHousing.id, label: typeOfHousing.name }
    }) ?? [],
    travel_costs: installation_team.travel_costs?.map((travelCost) => {
      return { value: travelCost.id, label: travelCost.name }
    }) ?? [],
    worker_compensation_attach: '',
    liability_expiration_attach: '',
    company_name: installation_team.company_name,
    phone: installation_team.phone
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<InstallationTeamFormValues>) => {
    const installation_team = {
      id: values.id,
      number_of_member: values.number_of_member,
      worker_compensation_expiration_date: values.worker_compensation_expiration_date,
      liability_expiration_date: values.liability_expiration_date,
      user_id: values.user_id.value,
      worker_compensation_attach: values.worker_compensation_attach,
      liability_expiration_attach: values.liability_expiration_attach,
      company_name: values.company_name,
      phone: values.phone,
      type_of_housings: values.type_housing.map((typeHousing: any) => typeHousing.value),
      travel_costs: values.travel_costs.map((travelCost: any) => travelCost.value)
    }


    router.post(route('installation_team.update', installation_team.id), {
      _method: 'PUT',
      ...installation_team
    }, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Update Installation Team'
      >
        <Head title="Update Installation Team" />
        <Formik<InstallationTeamFormValues>
          initialValues={initialValues}
          validationSchema={installationTeamSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, setFieldValue, values }) => (
            <InstallationTeamForm
              errors={errors}
              submitCount={submitCount}
              isCreate={false}
              type_of_housings={type_of_housings}
              users={users}
              setFieldValue={setFieldValue}
              values={values}
              travel_costs={travel_costs}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
