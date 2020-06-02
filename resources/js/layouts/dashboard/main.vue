<template>

    <div>

        <!-- Dashboard Header For Authenticated User -->
        <DashboardHeader></DashboardHeader>

        <!-- Place the custom route content here -->
        <router-view />

    </div>

</template>

<script>

    import DashboardHeader from './header/main.vue';

    export default {
        components: { DashboardHeader },
        data(){
            return {
                
            }
        }
    };

</script>