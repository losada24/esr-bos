import FacebookIcon from '@/Components/Icons/FacebookIcon'
import GithubIcon from '@/Components/Icons/GithubIcon'
import GoogleIcon from '@/Components/Icons/GoogleIcon'

const IsLogin = ({ allowRegistration }: { allowRegistration?: boolean }) => {
  let content

  if (allowRegistration !== undefined && allowRegistration) {
    content = <div className="text-center dark:text-white">
      Don&apos;t have an account ?&nbsp;
      <a
        href="/register"
        className="uppercase text-primary underline transition hover:text-black dark:hover:text-white"
      >
        SIGN UP
      </a>
    </div>
  }
  return content
}

const SocialNetworkSignUp = ({ isLogin, allowRegistration }: { isLogin: boolean, allowRegistration?: boolean }) => {
  return (
    <>
      <div className="relative my-7 text-center md:mb-9">
        <span className="absolute inset-x-0 top-1/2 h-px w-full -translate-y-1/2 bg-white-light dark:bg-white-dark" />
        <span className="relative bg-white px-2 font-bold uppercase text-white-dark dark:bg-dark dark:text-white-light">
          or
        </span>
      </div>
      <div className="mb-10 md:mb-[60px]">
        <ul className="flex justify-center gap-3.5">
        <li>
            <a
              href="/auth/google/redirect"
              className="inline-flex h-8 w-8 items-center justify-center rounded-full p-0 transition hover:scale-110"
              style={{
                background:
                  'linear-gradient(135deg, rgba(239, 18, 98, 1) 0%, rgba(67, 97, 238, 1) 100%)'
              }}
            >
              <GoogleIcon />
            </a>
          </li>
          <li>
            <a
              href="/auth/facebook/redirect"
              className="inline-flex h-8 w-8 items-center justify-center rounded-full p-0 transition hover:scale-110"
              style={{
                background:
                  'linear-gradient(135deg, rgba(239, 18, 98, 1) 0%, rgba(67, 97, 238, 1) 100%)'
              }}
            >
              <FacebookIcon />
            </a>
          </li>
          <li>
            <a
              href="/auth/github/redirect"
              className="inline-flex h-8 w-8 items-center justify-center rounded-full p-0 transition hover:scale-110"
              style={{
                background:
                  'linear-gradient(135deg, rgba(239, 18, 98, 1) 0%, rgba(67, 97, 238, 1) 100%)'
              }}
            >
              <GithubIcon className="fill-white h-5 w-5"/>
            </a>
          </li>
        </ul>
      </div>
      {isLogin
        ? <IsLogin allowRegistration={allowRegistration} />
        : (
          <div className="text-center dark:text-white">
            Already have an account ?&nbsp;
            <a
              href="/login"
              className="uppercase text-primary underline transition hover:text-black dark:hover:text-white"
            >
              SIGN IN
            </a>
          </div>
          )}
    </>
  )
}

export default SocialNetworkSignUp
