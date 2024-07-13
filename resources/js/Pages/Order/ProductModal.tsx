import { useState } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type OrderProduct, type ProductCategory, type ProductConfig, type TypeOfProduct, type ExtraWorks, type ProductCost } from '@/types'
import { Field, Form, Formik, type FormikHelpers } from 'formik'
import { type OrderProductExtraWorksFormValues, orderProductSchema } from './OrderCommon'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { getProductPrice } from '@/Utils/price'

const ProductModal = ({
  showModal,
  onClose,
  isCreated,
  typeOfProducts,
  productCategories,
  productConfigs,
  extraWorks,
  typeOfWork,
  productCosts,
  addOrderProduct
}: {
  showModal: boolean
  onClose: CallableFunction
  isCreated: boolean
  typeOfProducts: TypeOfProduct[]
  productCategories: ProductCategory[]
  productConfigs: ProductConfig[]
  extraWorks: ExtraWorks[]
  typeOfWork: number
  productCosts: ProductCost[]
  addOrderProduct: CallableFunction
}) => {
  const [productCategoryOptions, setProductCategoryOptions] = useState<ProductCategory[]>([])
  const [productConfigOptions, setProductConfigOptions] = useState<ProductConfig[]>([])
  const [plannedExtraWorksFormValues, setPlannedExtraWorksFormValues] = useState<OrderProductExtraWorksFormValues[]>(
    extraWorks.filter((extra) => extra.planned).map((extraWork) => ({
      extra_work_id: extraWork.id,
      extra_work_name: extraWork.name,
      extra_work_unit: extraWork.unit,
      number_of_sides: 0,
      checked: false,
      price: extraWork.price
    }))
  )

  const [notPlannedtExtraWorksFormValues, setNotPlannedExtraWorksFormValues] = useState<OrderProductExtraWorksFormValues[]>(
    extraWorks.filter((extra) => !extra.planned).map((extraWork) => ({
      extra_work_id: extraWork.id,
      extra_work_name: extraWork.name,
      extra_work_unit: extraWork.unit,
      number_of_sides: 0,
      checked: false,
      price: extraWork.price
    }))
  )

  const initialValues: OrderProduct = {
    id: 0,
    order_id: 0,
    qty: 0,
    height: 0,
    width: 0,
    unit_price: 0,
    total_price: 0,
    notes: '',
    product_config_id: 0,
    type_of_work_id: typeOfWork,
    storefront_area: 0,
    installation_other_level: false,
    product_category_id: 0,
    type_of_product_id: 0,
    extra_works: []
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderProduct>) => {
    let hasErrors = false
    notPlannedtExtraWorksFormValues.forEach((extraWork) => {
      if (extraWork.checked && extraWork.number_of_sides <= 0) {
        hasErrors = true
        helpers.setFieldError(`notPlannedExtraWork${extraWork.extra_work_id}_sides`, 'This field is required')
      }
    })

    if (!hasErrors) {
      const plannedExtraWorks = plannedExtraWorksFormValues.filter((extraWork) => extraWork.checked)
      const notPlannedExtraWorks = notPlannedtExtraWorksFormValues.filter((extraWork) => extraWork.checked).map((extraWork) => ({
        extra_work_id: extraWork.extra_work_id,
        number_of_sides: extraWork.number_of_sides,
        price: extraWork.price * extraWork.number_of_sides
      }))

      const product: OrderProduct = {
        ...values,
        extra_works: [
          ...plannedExtraWorks,
          ...notPlannedExtraWorks
        ]
      }

      const unit_price = getProductPrice(product, productCosts)
      product.unit_price = unit_price
      product.total_price = unit_price * product.qty
      // setPlannedExtraWorksFormValues(plannedExtraWorksFormValues.map((extraWork) => ({ ...extraWork, checked: false })))
      // setNotPlannedExtraWorksFormValues(plannedExtraWorksFormValues.map((extraWork) => ({ ...extraWork, checked: false })))
      addOrderProduct(product)
      onClose(false)
    }
  }

  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">{isCreated ? 'Add Product' : 'Edit Product'}</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className="h-[550px] overflow-y-scroll">
            <Formik<OrderProduct>
                initialValues={initialValues}
                validationSchema={orderProductSchema}
                onSubmit={handleSubmit}
              >
                {({ errors, submitCount, setFieldValue, values }) => (
                  <Form>
                    <div className='grid gap-4 grid-cols-3'>
                      <div className={submitCount ? (errors.type_of_product_id) ? 'has-error' : 'has-success' : ''}>
                        <label htmlFor="type_of_product_id">Type of Product</label>
                          <Field
                            id="type_of_product_id"
                            name="type_of_product_id"
                            className="form-select"
                            autoComplete="type_of_product_id"
                            placeholder='Type of Product'
                            as="select"
                            onChange={(e) => {
                              const id = parseInt(e.target.value)
                              setFieldValue('type_of_product_id', id)
                              setProductConfigOptions([])
                              if (id !== 3) {
                                setFieldValue('storefront_area', 0)
                              }
                              setProductCategoryOptions(productCategories.filter((productCategory) => productCategory.type_of_products_id === id))
                            }}
                          >
                            <option value="0">Type of Product</option>
                            {typeOfProducts.map((typeOfProduct, index) => (
                              <option key={index} value={typeOfProduct.id}>{typeOfProduct.name}</option>
                            ))}
                          </Field>
                          {(submitCount && errors.type_of_product_id) ? <InputError message={errors.type_of_product_id} className="mt-2" /> : ''}
                        </div>
                        <div className={submitCount ? (errors.product_category_id) ? 'has-error' : 'has-success' : ''}>
                          <label htmlFor="product_category_id">Product Category</label>
                            <Field
                              id="product_category_id"
                              name="product_category_id"
                              className="form-select"
                              autoComplete="product_category_id"
                              placeholder='Product Category'
                              as="select"
                              onChange={(e) => {
                                const id = parseInt(e.target.value)
                                setFieldValue('product_category_id', id)
                                setProductConfigOptions(productConfigs.filter((productConfig) => productConfig.product_categories_id === id))
                              }}
                            >
                              <option value="0">Product Category</option>
                              {productCategoryOptions.map((productCategory, index) => (
                                <option key={index} value={productCategory.id}>{productCategory.name}</option>
                              ))}
                            </Field>
                            {(submitCount && errors.product_category_id) ? <InputError message={errors.product_category_id} className="mt-2" /> : ''}
                        </div>
                        <div className={submitCount ? (errors.product_config_id) ? 'has-error' : 'has-success' : ''}>
                          <label htmlFor="product_config_id">Product Config</label>
                            <Field
                              id="product_config_id"
                              name="product_config_id"
                              className="form-select"
                              autoComplete="product_config_id"
                              placeholder='Product Config'
                              as="select"
                              onChange={(e) => {
                                const id = parseInt(e.target.value)
                                setFieldValue('product_config_id', id)
                              }}
                            >
                              <option value="0">Product Config</option>
                              {productConfigOptions.map((product_config, index) => (
                                <option key={index} value={product_config.id}>{product_config.name}</option>
                              ))}
                            </Field>
                            {(submitCount && errors.product_config_id) ? <InputError message={errors.product_config_id} className="mt-2" /> : ''}
                        </div>
                        <div className={submitCount ? (errors.width) ? 'has-error' : 'has-success' : ''}>
                          <label htmlFor="width">Width</label>
                          <Field
                            id="width"
                            name="width"
                            className="form-input text-right"
                            autoComplete="width"
                            placeholder='Width'
                            type='number'
                          />
                          {(submitCount && errors.width) ? <InputError message={errors.width} className="mt-2" /> : ''}
                        </div>
                        <div className={submitCount ? (errors.height) ? 'has-error' : 'has-success' : ''}>
                          <label htmlFor="height">Height</label>
                          <Field
                            id="height"
                            name="height"
                            className="form-input text-right"
                            autoComplete="height"
                            placeholder='Height'
                            type='number'
                          />
                          {(submitCount && errors.height) ? <InputError message={errors.height} className="mt-2" /> : ''}
                        </div>
                        <div className={submitCount ? (errors.qty) ? 'has-error' : 'has-success' : ''}>
                          <label htmlFor="qty">Qty</label>
                          <Field
                            id="qty"
                            name="qty"
                            className="form-input text-right"
                            autoComplete="qty"
                            placeholder='Qty'
                            type='number'
                          />
                          {(submitCount && errors.qty) ? <InputError message={errors.qty} className="mt-2" /> : ''}
                        </div>
                        {values.type_of_product_id === 3 && (
                          <div className={submitCount ? (errors.storefront_area) ? 'has-error' : 'has-success' : ''}>
                            <label htmlFor="storefront_area">Storefront Area</label>
                            <Field
                              id="storefront_area"
                              name="storefront_area"
                              className="form-input text-right"
                              autoComplete="storefront_area"
                              placeholder='Qty'
                              type='number'
                            />
                            {(submitCount && errors.storefront_area) ? <InputError message={errors.storefront_area} className="mt-2" /> : ''}
                          </div>
                        )}
                        {values.type_of_product_id === 2 && (
                          <div className='inline-flex items-end'>
                            <div className='flex'>
                              <Field
                                id={'installation_other_level'}
                                name={'installation_other_level'}
                                className="form-checkbox"
                                type='checkbox'
                              />
                              <label htmlFor={'installation_other_level'}>Installation Other Level</label>
                            </div>
                          </div>
                        )}
                  </div>
                  <fieldset className='p-3 border rounded-xl mt-3'>
                    <legend className='text-lg font-semibold px-3'>Planned Extra Works</legend>
                    <div className='grid gap-4 grid-cols-2'>
                      {plannedExtraWorksFormValues.map((extraWork) => (
                        <div key={extraWork.extra_work_id} className='inline-flex items-end'>
                          <div className='flex'>
                            <Field
                              id={`plannedExtraWork${extraWork.extra_work_id}`}
                              name={`plannedExtraWork${extraWork.extra_work_id}`}
                              className="form-checkbox"
                              type='checkbox'
                              checked={extraWork.checked}
                              onChange={(e) => {
                                setPlannedExtraWorksFormValues(plannedExtraWorksFormValues.map((extra) => {
                                  if (extra.extra_work_id === extraWork.extra_work_id) {
                                    return {
                                      ...extra,
                                      checked: e.target.checked
                                    }
                                  }
                                  return extra
                                }))
                              }}
                            />
                            <label htmlFor={`plannedExtraWork${extraWork.extra_work_id}`}>{extraWork.extra_work_name}</label>
                          </div>
                        </div>
                      ))}
                    </div>
                  </fieldset>
                  <fieldset className='p-3 border rounded-xl mt-3'>
                    <legend className='text-lg font-semibold px-3'>Not Planned Extra Works</legend>
                    <div className='grid gap-4 grid-cols-1'>
                      {notPlannedtExtraWorksFormValues.map((extraWork) => (
                        <div key={extraWork.extra_work_id} className={extraWork.extra_work_unit === 'side' ? 'grid gap-4 grid-cols-12' : 'grid gap-4 grid-cols-1'}>
                          <div className='inline-flex items-end col-span-9'>
                            <div className='flex'>
                              <Field
                                id={`notPlannedExtraWork${extraWork.extra_work_id}_checkbox`}
                                name={`notPlannedExtraWork${extraWork.extra_work_id}_checkbox`}
                                className="form-checkbox"
                                type='checkbox'
                                checked={extraWork.checked}
                                onChange={(e) => {
                                  setNotPlannedExtraWorksFormValues(notPlannedtExtraWorksFormValues.map((extra) => {
                                    if (extra.extra_work_id === extraWork.extra_work_id) {
                                      return {
                                        ...extra,
                                        checked: e.target.checked
                                      }
                                    }
                                    return extra
                                  }))
                                }}
                              />
                              <label htmlFor={`plannedExtraWork${extraWork.extra_work_id}`}>{extraWork.extra_work_name}</label>
                            </div>
                          </div>
                          {extraWork.extra_work_unit === 'side' && (
                            <div className={'col-span-3'}>
                              <label htmlFor={`side${extraWork.extra_work_id}`}>Sides</label>
                              <Field
                                id={`notPlannedExtraWork${extraWork.extra_work_id}_sides`}
                                name={`notPlannedExtraWork${extraWork.extra_work_id}_sides`}
                                className="form-input text-right"
                                value={extraWork.number_of_sides}
                                type='number'
                                onChange={(e) => {
                                  setNotPlannedExtraWorksFormValues(notPlannedtExtraWorksFormValues.map((extra) => {
                                    if (extra.extra_work_id === extraWork.extra_work_id) {
                                      return {
                                        ...extra,
                                        number_of_sides: parseInt(e.target.value)
                                      }
                                    }
                                    return extra
                                  }))
                                }}
                              />
                              {(submitCount && (errors as any)[`notPlannedExtraWork${extraWork.extra_work_id}_sides`]) ? <InputError message={(errors as any)[`notPlannedExtraWork${extraWork.extra_work_id}_sides`]} className="mt-2" /> : ''}
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                  </fieldset>
                  <div className="flex items-center justify-between mt-4">
                    <button className='btn btn-danger uppercase' onClick={ (e) => {
                      e.preventDefault()
                      onClose(false)
                    }}>Cancel</button>
                    <PrimaryButton className="btn btn-primary" type='submit'>
                      Add Product
                    </PrimaryButton>
                  </div>
                </Form>
                )}
            </Formik>
          </div>
        </div>
    </Modal>
  )
}

export default ProductModal
