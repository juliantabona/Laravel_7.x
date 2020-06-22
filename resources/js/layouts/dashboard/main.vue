<template>

    <div>

        <!-- Dashboard Header For Authenticated User -->
        <DashboardHeader :heading="heading"></DashboardHeader>

        <!-- Place the custom route content here -->
        <router-view @changeHeading="changeHeading($event)" />

    </div>

</template>

<script>

    import DashboardHeader from './header/main.vue';

    export default {
        components: { DashboardHeader },
        data(){
            return {
                heading: null
            }
        },
        methods: {
            changeHeading(name){
                this.heading = name;
            }
        },
    };

</script>