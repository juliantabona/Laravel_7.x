<template>

    <div>

        <!-- Event Settings -->
        <div class="mt-2">

            <Tabs v-model="activeNavTab" type="card" style="overflow: visible;" :animated="false" name="crud-api-event-tabs">

                <!-- Screen Settings Navigation Tabs -->
                <TabPane v-for="(currentTabName, key) in navTabs" :key="key" :label="currentTabName" :name="currentTabName"></TabPane>

            </Tabs> 

            <!-- General -->
            <general v-show="activeNavTab == 'General'" v-bind="$props"></general>

            <!-- Payment Methods -->
            <paymentMethods v-show="activeNavTab == 'Payment Methods'" v-bind="$props"></paymentMethods>

            <!-- Payment Success -->
            <paymentSuccess v-show="activeNavTab == 'Payment Success'" v-bind="$props"></paymentSuccess>

            <!-- Payment Fail -->
            <paymentFail v-show="activeNavTab == 'Payment Fail'" v-bind="$props"></paymentFail>

        </div>
    </div>

</template>

<script>
    
    import general from './general/main.vue';
    
    import paymentMethods from './payment-methods/main.vue';
    
    import paymentSuccess from './payment-success/main.vue';
    
    import paymentFail from './payment-fail/main.vue';
    

    export default {
        props: {
            index: {
                type: Number,
                default: null
            },
            event: {
                type: Object,
                default: null
            },
            events: {
                type: Array,
                default: () => []
            },
            screen: {
                type: Object,
                default: null
            },
            display: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
            isCloning: {
                type: Boolean,
                default: false
            },
            isEditing: {
                type: Boolean,
                default: false
            },
        },
        components: {
            general, paymentMethods, paymentSuccess, paymentFail
        },
        data(){
            return{
                localEvent: this.event,
                activeNavTab: 'General'
            }
        }, 
        computed: {
            navTabs(){
                var tabs = ['General', 'Payment Methods', 'Payment Success', 'Payment Fail'];

                return tabs;
            }
        },
        created(){

        }
    }
</script>