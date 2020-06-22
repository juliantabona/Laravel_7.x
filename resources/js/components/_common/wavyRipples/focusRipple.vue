<template>

    <!-- Ripple Switch  -->
    <div :style="{ position:'relative' }">

        <!-- Ripple Wave  -->
        <div v-if="ripple" :class="'circle-ripple'+ (color == 'green' ? ' green-circle-ripple' : ' blue-circle-ripple' )" 
            :style="waveStyle">
        </div>

        <div :style="{ position:'relative', zIndex:'2' }">
            
            <!-- Content  -->
            <slot></slot>

        </div>
    </div>
    
</template>

<script type="text/javascript">

    export default {
        props: {
            color: {
                type: String,
                default: 'green'
            },
            waveStyle: {
                type: Object,
                default: null
            },
            ripple: {
                type: Boolean,
                default: false
            }
        }
    }
    
</script>
