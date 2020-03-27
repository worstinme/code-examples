<template lang="pug">
  div.q-mt-md
    template(v-if="order.model_type === 'bike'")
      div(:class="$q.platform.is.mobile?'text-h2':'text-h1'") {{ $t('selectHelmets') }}
      p.text-small.q-my-sm {{ $t('helmetsInfo') }}
      q-list.extras.q-my-lg
        template(v-for="helmet in helmets")
          q-item.q-mt-md(:key="`helmet-${helmet.id}`")
            q-item-section(avatar top)
              img(:src="helmet.preview.url")
            q-item-section(top v-if="$q.platform.is.mobile")
              q-item-label.extras-title(lines="2") {{ helmet.name }}
              q-item-label(lines="1")
                span.extras-price
                  template(v-if="helmet.price > 0") {{  helmet.price | displayCurrency(true) }}
                  template(v-else) {{ $t('free') }}
              q-item-label(lines="1" v-if="orderExtras[helmet.id] && orderExtras[helmet.id].sum > 0")
                span.extras-price.text-black.text-weight-bold {{  orderExtras[helmet.id].sum | displayCurrency(true) }} {{ $t('for') }} {{ orderExtras[helmet.id].quantity }}
            template(v-else)
              q-item-section(center)
                q-item-label.helmet-title(lines="2") {{ helmet.name }}
              q-item-section.text-right(center)
                q-item-label(lines="1")
                  span.extras-price
                    template(v-if="helmet.price > 0") {{  helmet.price | displayCurrency(true) }}
                    template(v-else) {{ $t('free') }}
                q-item-label(lines="1" v-if="orderExtras[helmet.id] && orderExtras[helmet.id].sum > 0")
                  span.extras-price.text-black.text-weight-bold {{  orderExtras[helmet.id].sum | displayCurrency(true) }} {{ $t('for') }} {{ orderExtras[helmet.id].quantity }}
            q-item-section(center side)
              q-btn-group(outline)
                q-btn(outline color="accent" text-color="accent" :disable="requestInProcess || !orderExtras[helmet.id] || orderExtras[helmet.id].quantity < 1" @click="removeExtra(helmet.id)")
                  q-icon(name="fas fa-minus" size="xs" color="primary")
                q-btn(outline color="accent" text-color="accent" disable style="width: 40px;padding: 0")
                  span.text-black {{ orderExtras[helmet.id] ? orderExtras[helmet.id].quantity : 0 }}
                q-btn(outline color="accent" text-color="accent" @click="addExtra(helmet.id)" :disable="requestInProcess")
                  q-icon(name="fas fa-plus" size="xs" color="primary")
          q-separator(spaced inset)
      div(:class="$q.platform.is.mobile?'text-h2':'text-h1'") {{ $t('accessories') }}
      p.text-small.q-my-sm {{ $t('accessoriesInfo') }}
      q-list.extras.q-mt-md
        template(v-for="accessory in accessories")
          q-item.q-mt-md(:key="`accessory-${accessory.id}`")
            q-item-section(avatar top)
              img(:src="accessory.preview.url")
            q-item-section(top v-if="$q.platform.is.mobile")
              q-item-label.extras-title(lines="2") {{ accessory.name }}
              q-item-label.extras-info(lines="1" v-if="accessory.questionTitle")
                span.text-primary.cursor-pointer(@click="questionDialogShow = accessory") {{ accessory.questionTitle }}
              q-item-label(lines="1")
                span.extras-price
                  template(v-if="accessory.price > 0") {{  accessory.price | displayCurrency(true) }}
                  template(v-else) {{ $t('free') }}
            template(v-else)
              q-item-section(center)
                q-item-label.extras-title(lines="2") {{ accessory.name }}
                q-item-label.extras-info(lines="1" v-if="accessory.questionTitle")
                  span.text-primary.cursor-pointer(@click="questionDialogShow = accessory") {{ accessory.questionTitle }}
              q-item-section.text-right(center)
                q-item-label(lines="1")
                  span.extras-price
                    template(v-if="accessory.price > 0") {{  accessory.price | displayCurrency(true) }}
                    template(v-else) {{ $t('free') }}
            q-item-section(center side)
              img.cursor-pointer(src="/statics/icons/checkbox-checked.svg" v-if="orderExtras[accessory.id]" @click="removeExtra(accessory.id)")
              img.cursor-pointer(src="/statics/icons/checkbox.svg" v-else @click="addExtra(accessory.id)")
          q-separator(spaced inset)
    q-dialog(v-model="questionDialogShow" :position="$q.platform.is.mobile ? 'bottom' : 'standard'" )
      q-card.card-wide(v-if="questionDialogExtra")
        q-card-section.row.items-center
          .text-h6.col {{ questionDialogExtra.questionTitle }}
          q-space
          q-icon(name="fas fa-times" flat round dense v-close-popup)
        q-scroll-area.q-card-section
          q-carousel(v-if="questionDialogExtra.images" v-model="questionDialogSlide" swipeable animated thumbnails infinite)
            q-carousel-slide(v-for="(image, key) in questionDialogExtra.images" :key="'slide-'+questionDialogExtra.id+'-'+key" :name="key" :img-src="image.url")
          div(v-html="questionDialogExtra.question")
</template>
<script>
import Order from '../models/Order'
import Extra from '../models/Extra'
import ordersExtrasActions from '../mixins/ordersExtrasActions'

export default {
  name: 'OrderExtras',
  mixins: [ordersExtrasActions],
  props: {
    order: {
      type: Order,
      required: true
    }
  },
  created () {
    if (!Extra.exists()) {
      Extra.api().fetch()
    }
  },
  data () {
    return {
      questionDialogExtra: null,
      questionDialogSlide: 0
    }
  },
  computed: {
    questionDialogShow: {
      set (value) {
        if (value) {
          this.questionDialogSlide = 0
          this.questionDialogExtra = value
        } else {
          this.questionDialogExtra = null
        }
      },
      get () {
        return this.questionDialogExtra !== null
      }
    },
    helmets () {
      return this.extras.filter(extra => extra.category === 2 && extra.type === this.order.model_type)
    },
    accessories () {
      return this.extras.filter(extra => extra.category === 5 && extra.type === this.order.model_type)
    }
  },
  methods: {
  },
  i18n: {
    messages: {
      ru: {
        selectHelmets: 'Выберите шлемы',
        helmetsInfo: 'Вы можете выбрать до 2-х шлемов. Они предоставляются на весь срок аренды и доставляются вместе с байком',
        accessories: 'Аксессуары',
        accessoriesInfo: 'Для более комфортного райдинга',
        free: 'Бесплатно'
      }
    }
  }
}
</script>
<style lang="stylus">
.extras
  margin-left -16px
  margin-right -16px
  font-size 14px
  line-height 17px
  color black
  @media(max-width $breakpoint-sm-max)
    .extras-price
      font-size: 13px;
      line-height: 16px;
      color $grey-light
</style>
