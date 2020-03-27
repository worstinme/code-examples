import Extra from 'src/models/Extra'
import OrderExtra from 'src/models/OrderExtra'

export default {
  data () {
    return {
      requestInProcess: false
    }
  },
  computed: {
    extras () {
      return Extra.all()
    },
    orderExtras () {
      const orderExtras = {}
      this.order.extras.forEach((extra) => {
        orderExtras[extra.extra_id] = extra
      })
      return orderExtras
    }
  },
  methods: {
    removeExtra (extraId) {
      return new Promise((resolve, reject) => {
        const orderExtra = this.orderExtras[extraId]
        if (orderExtra) {
          this.requestInProcess = true
          if (orderExtra.quantity > 1) {
            orderExtra.updateModelAttributes({
              quantity: orderExtra.quantity - 1
            }).then((response) => {
              this.requestInProcess = false
              resolve(response)
            }).catch((error) => {
              this.requestInProcess = false
              reject(error)
            })
          } else {
            orderExtra.deleteModel().then((response) => {
              this.requestInProcess = false
              resolve(response)
            }).catch((error) => {
              this.requestInProcess = false
              reject(error)
            })
          }
        }
        resolve()
      })
    },
    addExtra (extraId) {
      return new Promise((resolve, reject) => {
        const orderExtra = this.orderExtras[extraId]
        if (orderExtra) {
          this.requestInProcess = true
          orderExtra.updateModelAttributes({
            quantity: orderExtra.quantity + 1,
            token: this.order.token
          }).then((response) => {
            this.requestInProcess = false
            resolve(response)
          }).catch((error) => {
            this.requestInProcess = false
            reject(error)
          })
        } else {
          OrderExtra.createModel({
            order_id: this.order.id,
            token: this.order.token,
            extra_id: extraId
          }).then((response) => {
            this.requestInProcess = false
            resolve(response)
          }).catch((error) => {
            this.requestInProcess = false
            reject(error)
          })
        }
        resolve()
      })
    }
  }
}
