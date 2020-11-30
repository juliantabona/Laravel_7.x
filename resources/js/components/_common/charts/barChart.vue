<script>

import { Bar } from 'vue-chartjs'

export default {
  extends: Bar,
  props: {
    chartdata: {
      type: Object,
      default: null
    },
    options: {
      type: Object,
      default: null
    }
  },
  watch: {
      //  Watch for changes on the chartData
      chartdata: {
          handler: function (newVal, oldVal) {
              this.renderChart(newVal, this.options);
          },
          deep: true
      },
      //  Watch for changes on the options
      options: {
          handler: function (newVal, oldVal) {
            this.renderChart(this.chartdata, newVal);
          },
          deep: true
      },
  },
  mounted () {
    this.renderChart(this.chartdata, this.options)
  }
}

</script>