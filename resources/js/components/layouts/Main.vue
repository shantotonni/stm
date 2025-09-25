<template>
  <div id="wrapper">
    <Navbar v-if="canShowSidebar" />
    <Sidebar v-if="canShowSidebar" />
    <div class="content-page">
      <router-view></router-view>
      <Footer/>
    </div>
  </div>
</template>
<script>
import Sidebar from './Sidebar'
import Footer from './Footer'
import Navbar from "./Navbar";

export default {
  components: {
    Navbar,
    Sidebar,
    Footer
  },
  computed: {
    canShowSidebar() {
      let is_change_password = localStorage.getItem("is_change_password")
      return is_change_password === "Y"
    }
  }
}
</script>
