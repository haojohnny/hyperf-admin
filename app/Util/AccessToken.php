<?php


namespace App\Util;

use App\Constants\Constants;
use App\Exception\InvalidConfigException;
use App\Exception\LoginException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use InvalidArgumentException;

class AccessToken
{
    private static $alg;

    private static $app_key;

    public function __construct()
    {
        self::$alg = 'HS256';
        self::$app_key = config('app_key');
    }

    public function encode(array $payload): string
    {
        return JWT::encode($payload, self::$app_key, self::$alg);
    }

    public function decode(string $jwt): array
    {
        try {
            $decode = JWT::decode($jwt, self::$app_key, [self::$alg]);

            return (array)$decode;
        } catch (ExpiredException $exception) {
            //过期token
            throw new LoginException('token过期！', -1);
        } catch (InvalidArgumentException $exception) {
            //参数错误
            throw new LoginException('token参数非法！', -1);
        } catch (\UnexpectedValueException $exception) {
            //token无效
            throw new LoginException('token无效！', -1);
        } catch (\Exception $exception) {
            throw new LoginException($exception->getMessage(), -1);
        }
    }

    /**
     * 创建token
     * @return string
     */
    public function createToken(Payload $payload)
    {
        $token = $this->encode($payload->toArray());

        return $token;
    }

    public function checkToken(string $token): Payload
    {
        if (empty($token)) {
            throw new LoginException('token不能为空！', -1);
        }

        $jwt = $this->decode($token);

        if (is_null($jwt)) {
            throw new LoginException('token无效！', -1);
        }

        $payload = new Payload($jwt);

        if (Constants::SCOPE_ROLE !== $payload['scopes']) {
            throw new LoginException('refresh-token参数非法！', -2);
        }

        return $payload;

    }

    /**
     * 刷新token
     * @param $refresh
     * @return string
     */
    public function refreshToken($refresh): string
    {
        if (empty($refresh)) {
            throw new LoginException('参数有误！');
        }

        $jwt = $this->decode($refresh);

        if (is_null($jwt)) {
            throw new LoginException('refresh-token参数有误！', -2);
        }

        if (Constants::SCOPE_REFRESH !== $jwt['scopes']) {
            throw new LoginException('refresh-token参数非法！', -2);
        }

        $data = $jwt['data'];

        return '';
    }

}