<template>

    <!-- Focus Ripple  -->
    <focusRipple :ripple="ripple">

        <!-- Button  -->
        <Button :type="type" :size="size" :disabled="disabled" :loading="loading" :class="buttonClass">
            <Icon v-if="showIcon && iconDirection == 'left'" :type="icon" :size="20" style="margin-top: -4px;"/>
            <span><slot></slot></span>
            <Icon v-if="showIcon && iconDirection == 'right'" :type="icon" :size="20" style="margin-top: -4px;"/>
        </Button>

    </focusRipple>
    
</template>

<script type="text/javascript">
    
    /*  Ripples  */
    import focusRipple from './../wavyRipples/focusRipple.vue';

    export default {
        props:{
            type: {
                type: String,
                default: 'primary'
            },
            size: {
                type: String,
                default: 'small'
            },
            icon: {
                type: String,
                default: 'ios-send-outline'
            },
            iconDirection: {
                type: String,
                default: 'right'
            },
            showIcon: {
                type: Boolean,
                default: false
            },
            ripple: {
                type: Boolean,
                default: false
            },
            disabled: {
                type: Boolean,
                default: false
            },
            loading: {
                type: Boolean,
                default: false
            },
            buttonClass: {
                type: [Array, Object, String],
                default: ''
            }
        },
        components: { focusRipple }
    }
    
</script>
