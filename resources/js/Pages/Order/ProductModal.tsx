import React, { useState } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type OrderProduct, type ProductCategory, type ProductConfig, type TypeOfProduct, type ProductCost, TypeOfWork } from '@/types'
import { Field, Form, Formik, type FormikHelpers } from 'formik'
import { type OrderProductExtraWorksFormValues, orderProductSchema, getValueIdNotNull } from './OrderCommon'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { getProductPriceWithExtraWorks, getProductPrice, getProductExtraWorkPrice } from '@/Utils/price'
import { PAYMENT_METHODS, SERVICES, STOREFRONT_CATEGORY, PIVOT_CONFIG} from '@/Utils/constants'

const ProductModal = ({
  showModal,
  onClose,
  isCreated,
  typeOfProducts,
  productCategories,
  productConfigs,
  typeOfWork,
  listTypeOfWork,
  productCosts,
  addOrderProduct,
  service
}: {
  showModal: boolean
  onClose: CallableFunction
  isCreated: boolean
  typeOfProducts: TypeOfProduct[]
  productCategories: ProductCategory[]
  productConfigs: ProductConfig[]
  typeOfWork: number
  listTypeOfWork: TypeOfWork[]
  productCosts: ProductCost[]
  service: string
  addOrderProduct: CallableFunction
}) => {
  const [productCategoryOptions, setProductCategoryOptions] = useState<ProductCategory[]>([])
  const [productConfigOptions, setProductConfigOptions] = useState<ProductConfig[]>([])
  const [plannedExtraWorksFormValues, setPlannedExtraWorksFormValues] = useState<OrderProductExtraWorksFormValues[]>([])

  const initialValues: OrderProduct = {
    id: 0,
    order_id: 0,
    qty: 0,
    height: 0,
    width: 0,
    unit_price: 0,
    unit_price_with_extraworks: 0,
    total_price_with_extraworks: 0,
    extra_work_price: 0,
    total_price: 0,
    notes: '',
    product_config_id: 0,
    type_of_work_id: typeOfWork,
    storefront_area: 0,
    installation_other_level: false,
    product_category_id: 0,
    type_of_product_id: 0,
    extra_works: [],
    pivot_cost: 0
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderProduct>) => {
    const plannedExtraWorks = plannedExtraWorksFormValues.filter((extraWork) => extraWork.checked)
    const product: OrderProduct = {
      ...values,
      type_of_work_id: values.type_of_work_id !== 0 ? values.type_of_work_id : getValueIdNotNull(values.type_of_work_id),
      extra_works: plannedExtraWorks
    }
    const unit_price = getProductPrice(product, productCosts)
    const unit_price_with_extrawork = getProductPriceWithExtraWorks(product, productCosts)
    product.extra_work_price = getProductExtraWorkPrice(product) ?? 0
    product.unit_price = unit_price

    if (product.type_of_product_id !== STOREFRONT_CATEGORY) {
      product.total_price = unit_price * product.qty
    } else {
      product.total_price = unit_price
    }

    product.unit_price_with_extraworks = unit_price_with_extrawork
    product.total_price_with_extraworks = unit_price_with_extrawork + product.total_price
    addOrderProduct(product)
    onClose(false)
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
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                              const id = parseInt(e.target.value)
                              setFieldValue('type_of_product_id', id)
                              setProductConfigOptions([])
                              if (id !== 3) {
                                setFieldValue('storefront_area', 0)
                              }
                              setProductCategoryOptions(productCategories.filter((productCategory) => productCategory.type_of_products_id === id))

                              const extraWorks = typeOfProducts.find((typeOfProduct) => typeOfProduct.id === id)?.extra_works.map((extraWork) => ({
                                extra_work_id: extraWork.id,
                                extra_work_name: extraWork.name,
                                extra_work_unit: extraWork.unit,
                                amount: 0,
                                checked: false,
                                price: extraWork.price
                              })) ?? []
                              setPlannedExtraWorksFormValues(extraWorks)
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
                              onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
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
                              onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
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
                        <div className={submitCount ? (errors.type_of_work_id) ? 'has-error' : 'has-success' : ''}>
                          <label htmlFor="type_of_work">Type of Work</label>
                          <Field
                            id="type_of_work_id"
                            name="type_of_work_id"
                            className="form-select"
                            autoComplete="type_of_work_id"
                            placeholder='Type of Work'
                            as="select"
                            onChange={(e: { target: { value: string } }) => {
                              const type_of_work_id = parseInt(e.target.value)
                              setFieldValue('type_of_work_id', type_of_work_id)
                            }}
                          >
                            <option value="0">Type of Work</option>
                            {listTypeOfWork.map((listTypeOfWork, index) => (
                              <option key={index} value={listTypeOfWork.id}>{listTypeOfWork.name}</option>
                            ))}
                          </Field>
                          {(submitCount && errors.type_of_work_id) ? <InputError message={errors.type_of_work_id} className="mt-2" /> : ''}
                        </div>

                        <div className='col-span-3'>
                            <label htmlFor="notes">Notes</label>
                              <Field
                                id="notes"
                                name="notes"
                                component="textarea"
                                rows="4"
                                className="form-textarea resize-none placeholder:text-white-dark"
                                placeholder='Notes'
                              />
                               {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
                        </div>
                        {(values.type_of_product_id === 3 && service === SERVICES.DELIVERY_AND_INSTALLATION) && (
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
                          {(values.product_config_id === 30 && service === SERVICES.DELIVERY_AND_INSTALLATION) && (
                          <div className={submitCount ? (errors.pivot_cost) ? 'has-error' : 'has-success' : ''}>
                            <label htmlFor="pivot_cost">Pivot Cost</label>
                            <Field
                              id="pivot_cost"
                              name="pivot_cost"
                              className="form-input text-right"
                              autoComplete="storefront_area"
                              placeholder='Pivot Cost'
                              type='number'
                            />
                            {(submitCount && errors.pivot_cost) ? <InputError message={errors.pivot_cost} className="mt-2" /> : ''}
                          </div>)}
                        {(values.type_of_product_id === 2 && service === SERVICES.DELIVERY_AND_INSTALLATION) &&  (
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
                  {(plannedExtraWorksFormValues.length > 0 && service === SERVICES.DELIVERY_AND_INSTALLATION) && (
                    <fieldset className='p-3 border rounded-xl mt-3'>
                      <legend className='text-lg font-semibold px-3'>Extra Works</legend>
                      <div className='grid gap-4 grid-cols-2'>
                        {plannedExtraWorksFormValues.map((extraWork) => (
                          <>
                            <div key={extraWork.extra_work_id} className='inline-flex items-end'>
                              <div className='flex'>
                                <Field
                                  id={`plannedExtraWork${extraWork.extra_work_id}`}
                                  name={`plannedExtraWork${extraWork.extra_work_id}`}
                                  className="form-checkbox"
                                  type='checkbox'
                                  checked={extraWork.checked}
                                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                                    const isChecked = e.target.checked
                                    setPlannedExtraWorksFormValues(plannedExtraWorksFormValues.map((extra) => {
                                      if (extra.extra_work_id === extraWork.extra_work_id) {
                                        return {
                                          ...extra,
                                          checked: isChecked,
                                          amount: isChecked && extra.amount <= 0 ? 1 : extra.amount
                                        }
                                      }
                                      return extra
                                    }))
                                  }}
                                />
                                <label htmlFor={`plannedExtraWork${extraWork.extra_work_id}`}>{extraWork.extra_work_name}</label>
                              </div>
                            </div>
                            <div className={submitCount ? (errors.storefront_area) ? 'has-error' : 'has-success' : ''}>
                              <label htmlFor={`extraWorkAmount${extraWork.extra_work_id}`}>Amount</label>
                              <Field
                                id={`extraWorkAmount${extraWork.extra_work_id}`}
                                name={`extraWorkAmount${extraWork.extra_work_id}`}
                                className="form-input text-right"
                                placeholder='Qty'
                                type='number'
                                value={extraWork.amount}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                                  const newAmount = parseInt(e.target.value)
                                  setPlannedExtraWorksFormValues(plannedExtraWorksFormValues.map((extra) => {
                                    if (extra.extra_work_id === extraWork.extra_work_id) {
                                      if (extra.checked && newAmount <= 0) {
                                        alert('Amount must be greater than zero when checked!')
                                        return extra
                                      }
                                      return {
                                        ...extra,
                                        amount: newAmount
                                      }
                                    }
                                    return extra
                                  }))
                                }}
                              />
                            </div>
                          </>
                        ))}
                      </div>
                    </fieldset>
                  )}
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
