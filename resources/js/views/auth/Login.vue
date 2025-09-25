<template>
  <div>
    <div class="wrapper-page">
      <div class="card overflow-hidden account-card mx-3">
        <div class="bg-primary p-4 text-white text-center position-relative">
          <h4 class="font-20 m-b-5">Welcome Back !</h4>
          <p class="text-white-50 mb-4">Sign in to continue to Survey Panel.</p>
        </div>
        <div class="account-card-content">
          <ValidationObserver v-slot="{ handleSubmit }">
            <form class="form-horizontal m-t-30" @submit.prevent="handleSubmit(onSubmit)">
              <ValidationProvider name="BMDC_NO" mode="eager" rules="required" v-slot="{ errors }">
                <div class="form-group">
                  <label>User Id</label>
                  <input type="text" class="form-control" :class="{'error-border': errors[0]}" id="BMDC_NO" v-model="BMDC_NO" name="BMDC_NO" placeholder="User Id" autocomplete="off">
                  <span class="error-message"> {{ errors[0] }}</span>
                </div>
              </ValidationProvider>

              <ValidationProvider name="Password" mode="eager" rules="required|min:6" v-slot="{ errors }">
                <div class="form-group">
                  <label for="user-password">Password</label>
                  <input type="password" v-model="password" class="form-control" :class="{'error-border': errors[0]}"
                         id="user-password" placeholder="Password" autocomplete>
                  <span class="error-message">{{ errors[0] }}</span>
                </div>
              </ValidationProvider>
              <submit-form name="Log In"/>
            </form>
          </ValidationObserver>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import {Common} from '../../mixins/common'
import moment from "moment";

export default {
  mixins: [Common],
  data() {
    return {
      BMDC_NO: '',
      password: '',
    }
  },
  computed: {
    now() {
      return moment()
    }
  },
  mounted() {
    document.title = 'Login | Medical Survey';
  },
  methods: {
    onSubmit() {
      this.$store.commit('submitButtonLoadingStatus', true);
      this.axiosPostWithoutToken('login', {
        BMDC_NO: this.BMDC_NO,
        password: this.password
      }, (response) => {
        console.log(response.user)
        localStorage.setItem("token", response.access_token);
        localStorage.setItem("is_change_password", response.user.is_change_password);
        this.successNoti('Successfully logged in.');
        this.$store.commit('submitButtonLoadingStatus', false);
        this.redirect(this.mainOrigin + 'dashboard')
      }, (error) => {
        this.errorNoti(error);
        this.$store.commit('submitButtonLoadingStatus', false);
      })
    }
  }
}
</script>

